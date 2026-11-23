<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentAndRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court1;
    protected Court $court2;
    protected PlaySession $session1;
    protected PlaySession $session2;
    protected User $customer;
    protected User $customer2;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->court1 = Court::create(['name' => 'Lapangan 1', 'sort_order' => 1, 'is_active' => true]);
        $this->court2 = Court::create(['name' => 'Lapangan 2', 'sort_order' => 2, 'is_active' => true]);

        $this->session1 = PlaySession::create([
            'label' => 'Sesi 1',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->session2 = PlaySession::create([
            'label' => 'Sesi 2',
            'start_time' => '11:00:00',
            'end_time' => '15:00:00',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Setting::set('price_single', 200000);
        Setting::set('price_package', 600000);
        Setting::set('hold_minutes', 15);
        Setting::set('renew_notice_remaining', 1);
        Setting::set('last_day_notice_hour', 6);

        $this->customer = User::create([
            'name' => 'Budi Customer',
            'phone' => '081234567890',
            'email' => 'budi@test.com',
            'role' => UserRole::CUSTOMER,
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->customer2 = User::create([
            'name' => 'Ahmad Customer',
            'phone' => '081234567899',
            'email' => 'ahmad@test.com',
            'role' => UserRole::CUSTOMER,
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin GOR',
            'phone' => '081100000001',
            'role' => UserRole::ADMIN,
            'password' => 'password123',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Pembayaran berhasil: status booking & payment menjadi paid, notifikasi terkirim.
     */
    public function test_payment_success(): void
    {
        Notification::fake();

        $booking = Booking::create([
            'code' => 'PAY00001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method' => 'simulated',
            'provider' => 'simulated',
            'amount' => 200000,
            'status' => PaymentStatus::PENDING,
            'reference' => 'SIM-' . $booking->code,
        ]);

        $response = $this->actingAs($this->customer)
            ->post("/booking/{$booking->code}/bayar/proses");

        $response->assertRedirect("/booking/{$booking->code}");

        $booking->refresh();
        $this->assertEquals(BookingStatus::PAID, $booking->status);

        $payment = Payment::where('booking_id', $booking->id)->where('status', PaymentStatus::PAID)->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->paid_at);

        Notification::assertSentTo($this->customer, PaymentSuccessNotification::class);
    }

    /**
     * 2. Pembayaran idempoten: eksekusi berulang tidak menduplikasi pembayaran.
     */
    public function test_payment_idempotent(): void
    {
        $booking = Booking::create([
            'code' => 'IDEM0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        $gateway = app(PaymentGateway::class);

        $p1 = $gateway->markAsPaid($booking, $this->customer);
        $p2 = $gateway->markAsPaid($booking, $this->customer);

        $this->assertEquals($p1->id, $p2->id);
        $this->assertEquals(BookingStatus::PAID, $booking->fresh()->status);
        $this->assertEquals(1, Payment::where('booking_id', $booking->id)->where('status', PaymentStatus::PAID)->count());
    }

    /**
     * 3. Tidak bisa membayar booking yang kedaluwarsa atau dibatalkan.
     */
    public function test_cannot_pay_expired_or_cancelled_booking(): void
    {
        // A. Booking kedaluwarsa
        $bExpired = Booking::create([
            'code' => 'EXPD0002',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::EXPIRED,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->subMinutes(5),
        ]);

        $responseExp = $this->actingAs($this->customer)->post("/booking/{$bExpired->code}/bayar/proses");
        $responseExp->assertSessionHas('error');
        $this->assertEquals(BookingStatus::EXPIRED, $bExpired->fresh()->status);

        // B. Booking dibatalkan
        $bCancelled = Booking::create([
            'code' => 'CNCL0002',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::CANCELLED,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(5),
        ]);

        $responseCnl = $this->actingAs($this->customer)->post("/booking/{$bCancelled->code}/bayar/proses");
        $responseCnl->assertSessionHas('error');
        $this->assertEquals(BookingStatus::CANCELLED, $bCancelled->fresh()->status);
    }

    /**
     * 4. Pembayaran gagal lalu coba lagi berhasil.
     */
    public function test_payment_failed_then_retry_success(): void
    {
        $booking = Booking::create([
            'code' => 'RETRY001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        // Simulasikan gagal
        $responseFail = $this->actingAs($this->customer)->post("/booking/{$booking->code}/bayar/gagal");
        $responseFail->assertRedirect("/booking/{$booking->code}/bayar");

        $this->assertEquals(BookingStatus::PENDING_PAYMENT, $booking->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => PaymentStatus::FAILED->value,
        ]);

        // Coba lagi dan berhasil
        $responseSuccess = $this->actingAs($this->customer)->post("/booking/{$booking->code}/bayar/proses");
        $responseSuccess->assertRedirect("/booking/{$booking->code}");

        $this->assertEquals(BookingStatus::PAID, $booking->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => PaymentStatus::PAID->value,
        ]);
    }

    /**
     * 5. Hanya pemilik booking yang dapat mengakses halaman bayar dan memprosesnya.
     */
    public function test_only_owner_can_pay(): void
    {
        $booking = Booking::create([
            'code' => 'OWN00001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        // Customer lain coba akses GET /bayar -> 403
        $this->actingAs($this->customer2)
            ->get("/booking/{$booking->code}/bayar")
            ->assertStatus(403);

        // Customer lain coba POST /bayar/proses -> 403
        $this->actingAs($this->customer2)
            ->post("/booking/{$booking->code}/bayar/proses")
            ->assertStatus(403);
    }

    /**
     * 6. PackageLifecycle di UI: progress bar 4 kotak datar dan chip state.
     */
    public function test_package_lifecycle_in_ui(): void
    {
        $now = Carbon::now('Asia/Jayapura');
        $booking = Booking::create([
            'code' => 'BULAN001',
            'user_id' => $this->customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        // Buat 4 slot: 3 lampau, 1 mendatang (sisa 1 pertemuan)
        $dates = [
            $now->copy()->subWeeks(3)->toDateString(),
            $now->copy()->subWeeks(2)->toDateString(),
            $now->copy()->subWeek()->toDateString(),
            $now->copy()->addDays(2)->toDateString(),
        ];

        foreach ($dates as $d) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court1->id,
                'play_session_id' => $this->session1->id,
                'play_date' => $d,
            ]);
        }

        $response = $this->actingAs($this->customer)->get("/booking/{$booking->code}");
        $response->assertStatus(200);
        $response->assertSee('Pertemuan 4 dari 4');
        $response->assertSee('package-progress-bar');
        $response->assertSee('Hampir Habis');
        $response->assertSee('Lanjutkan Sewa Bulanan');
    }

    /**
     * 7. Perpanjangan Bulanan berhasil: tanggal tepat (+7 sd +28 hari), renewed_from_id terisi, harga snapshot terbaru.
     */
    public function test_bulanan_renewal_success(): void
    {
        $now = Carbon::now('Asia/Jayapura');
        $lastMeetingDate = $now->copy()->addDays(2);

        $booking = Booking::create([
            'code' => 'ORIG0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        $dates = [
            $lastMeetingDate->copy()->subWeeks(3)->toDateString(),
            $lastMeetingDate->copy()->subWeeks(2)->toDateString(),
            $lastMeetingDate->copy()->subWeek()->toDateString(),
            $lastMeetingDate->toDateString(),
        ];

        foreach ($dates as $d) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court1->id,
                'play_session_id' => $this->session1->id,
                'play_date' => $d,
            ]);
        }

        // Ubah harga di pengaturan untuk memastikan snapshot baru dipakai
        Setting::set('price_package', 650000);

        $response = $this->actingAs($this->customer)->post("/booking/{$booking->code}/perpanjang");
        $response->assertRedirect();

        $renewalBooking = Booking::where('renewed_from_id', $booking->id)->first();
        $this->assertNotNull($renewalBooking);
        $this->assertEquals(BookingType::PACKAGE, $renewalBooking->type);
        $this->assertEquals(BookingStatus::PENDING_PAYMENT, $renewalBooking->status);
        $this->assertEquals(650000, $renewalBooking->total_price); // Snapshot harga terbaru
        $this->assertCount(4, $renewalBooking->slots);

        // 4 tanggal lanjutan: +7, +14, +21, +28 hari dari tanggal terakhir booking lama
        $expectedFirstDate = $lastMeetingDate->copy()->addWeek()->format('Y-m-d');
        $this->assertEquals($expectedFirstDate, $renewalBooking->slots->first()->play_date->format('Y-m-d'));
    }

    /**
     * 8. Perpanjangan Bulanan saat bentrok: menawarkan alternatif dan berhasil dipilih.
     */
    public function test_bulanan_renewal_conflict_and_alternatives(): void
    {
        $now = Carbon::now('Asia/Jayapura');
        $lastMeetingDate = $now->copy()->addDays(1);

        $booking = Booking::create([
            'code' => 'BTRK0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        foreach ([$lastMeetingDate->copy()->subWeeks(3), $lastMeetingDate->copy()->subWeeks(2), $lastMeetingDate->copy()->subWeek(), $lastMeetingDate] as $d) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court1->id,
                'play_session_id' => $this->session1->id,
                'play_date' => $d->toDateString(),
            ]);
        }

        // Blokir tanggal lanjutan ke-1 pada lapangan 1 sesi 1
        $conflictDate = $lastMeetingDate->copy()->addWeek()->toDateString();
        SlotBlock::create([
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $conflictDate,
            'reason' => 'Perawatan rutin',
            'created_by' => $this->admin->id,
        ]);

        // Coba perpanjang tanpa memilih alternatif: harus menampilkan halaman pilihan alternatif
        $response = $this->actingAs($this->customer)->post("/booking/{$booking->code}/perpanjang");
        $response->assertStatus(200);
        $response->assertSee('Slot Semula Sudah Tidak Tersedia');
        $response->assertSee('Pilihan Alternatif yang Tersedia');

        // Pilih alternatif Lapangan 2 (sesi tetap sesi 1)
        $responseAlt = $this->actingAs($this->customer)->post("/booking/{$booking->code}/perpanjang", [
            'alternative_court_id' => $this->court2->id,
        ]);

        $responseAlt->assertRedirect();

        $renewal = Booking::where('renewed_from_id', $booking->id)->first();
        $this->assertNotNull($renewal);
        $this->assertEquals($this->court2->id, $renewal->slots->first()->court_id);
    }

    /**
     * 9. Command bookings:notify-renewal idempoten dan tidak mengirim ke yang sudah diperpanjang.
     */
    public function test_renewal_notifications_idempotency_and_no_notification_if_already_renewed(): void
    {
        Notification::fake();

        $now = Carbon::now('Asia/Jayapura');
        $booking = Booking::create([
            'code' => 'NTFY0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        // Sisa 1 pertemuan
        $dates = [
            $now->copy()->subWeeks(3)->toDateString(),
            $now->copy()->subWeeks(2)->toDateString(),
            $now->copy()->subWeek()->toDateString(),
            $now->copy()->addDays(2)->toDateString(),
        ];
        foreach ($dates as $d) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court1->id,
                'play_session_id' => $this->session1->id,
                'play_date' => $d,
            ]);
        }

        // Eksekusi pertama: mengirim notifikasi
        $this->artisan('bookings:notify-renewal')
            ->expectsOutputToContain('Total 1 notifikasi terkirim')
            ->assertSuccessful();

        $booking->refresh();
        $this->assertNotNull($booking->renewal_notified_at);

        // Eksekusi kedua: idempoten, 0 notifikasi
        $this->artisan('bookings:notify-renewal')
            ->expectsOutputToContain('Total 0 notifikasi terkirim')
            ->assertSuccessful();
    }

    /**
     * 10. Banner permanent MODE UJI pada production jika payment driver adalah simulated.
     */
    public function test_production_simulated_banner(): void
    {
        $this->app->detectEnvironment(fn() => 'production');
        config(['gor.payment_driver' => 'simulated']);

        $booking = Booking::create([
            'code' => 'PROD0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        $response = $this->actingAs($this->customer)->get("/booking/{$booking->code}/bayar");
        $response->assertStatus(200);
        $response->assertSee('MODE UJI');
    }

    /**
     * 11. Halaman notifikasi menampilkan daftar dan tombol tandai semua dibaca.
     */
    public function test_notifications_page_and_mark_all_read(): void
    {
        // Kirim notifikasi buatan
        $booking = Booking::create([
            'code' => 'NOTF0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);

        $this->customer->notify(new PaymentSuccessNotification($booking));

        $this->assertEquals(1, $this->customer->unreadNotifications()->count());

        // Buka halaman notifikasi
        $response = $this->actingAs($this->customer)->get('/notifikasi');
        $response->assertStatus(200);
        $response->assertSee('Notifikasi');
        $response->assertSee('Pembayaran Berhasil');
        $response->assertSee('Tandai Semua Dibaca');

        // Tandai semua dibaca
        $responseRead = $this->actingAs($this->customer)->post('/notifikasi/baca-semua');
        $responseRead->assertRedirect('/notifikasi');

        $this->assertEquals(0, $this->customer->fresh()->unreadNotifications()->count());
    }
}
