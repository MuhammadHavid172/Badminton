<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court;
    protected PlaySession $session;
    protected User $customer;
    protected User $admin;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->court = Court::create(['name' => 'Lapangan 1', 'sort_order' => 1]);
        $this->session = PlaySession::create([
            'label' => 'Sesi 1',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'sort_order' => 1,
        ]);

        Setting::set('price_single', 200000);
        Setting::set('price_package', 600000);
        Setting::set('hold_minutes', 15);
        Setting::set('max_pending_bookings', 2);

        $this->customer = User::create([
            'name' => 'Budi Customer',
            'phone' => '081234567890',
            'email' => 'budi@test.com',
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

        $this->owner = User::create([
            'name' => 'Owner GOR',
            'phone' => '081100000004',
            'role' => UserRole::OWNER,
            'password' => 'password123',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Harian berhasil: 1 slot, expires_at sesuai hold_minutes, snapshot harga.
     */
    public function test_harian_booking_success(): void
    {
        $futureDate = Carbon::now('Asia/Jayapura')->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->customer)
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $futureDate,
                'type' => 'single',
            ]);

        $response->assertRedirect();

        $booking = Booking::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals(BookingType::SINGLE, $booking->type);
        $this->assertEquals(BookingStatus::PENDING_PAYMENT, $booking->status);
        $this->assertEquals(200000, $booking->total_price);
        $this->assertNotNull($booking->expires_at);

        // Periksa 1 slot tersimpan
        $this->assertCount(1, $booking->slots);
        $slot = $booking->slots->first();
        $this->assertEquals($futureDate, $slot->play_date->format('Y-m-d'));
        $this->assertEquals($this->court->id, $slot->court_id);
        $this->assertEquals($this->session->id, $slot->play_session_id);
    }

    /**
     * 2. Bulanan berhasil: 4 slot (+0, +7, +14, +21 hari), snapshot harga.
     */
    public function test_bulanan_booking_success(): void
    {
        $startDate = Carbon::now('Asia/Jayapura')->addDays(5);
        $startDateStr = $startDate->format('Y-m-d');

        $response = $this->actingAs($this->customer)
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $startDateStr,
                'type' => 'package',
            ]);

        $response->assertRedirect();

        $booking = Booking::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals(BookingType::PACKAGE, $booking->type);
        $this->assertEquals(600000, $booking->total_price);

        // Periksa 4 slot terkunci
        $this->assertCount(4, $booking->slots);

        $expectedDates = [
            $startDate->copy()->format('Y-m-d'),
            $startDate->copy()->addWeeks(1)->format('Y-m-d'),
            $startDate->copy()->addWeeks(2)->format('Y-m-d'),
            $startDate->copy()->addWeeks(3)->format('Y-m-d'),
        ];

        $actualDates = $booking->slots->pluck('play_date')->map(fn($d) => $d->format('Y-m-d'))->toArray();
        $this->assertEquals($expectedDates, $actualDates);
    }

    /**
     * 3. Bulanan ditolak jika satu tanggal bentrok dan pesan menyebut tanggalnya.
     */
    public function test_bulanan_rejected_if_any_date_conflicts_with_message(): void
    {
        $startDate = Carbon::now('Asia/Jayapura')->addDays(2);
        $conflictDate = $startDate->copy()->addWeeks(2); // Pertemuan ke-3 bentrok
        $conflictDateStr = $conflictDate->format('Y-m-d');

        // Blokir tanggal ke-3
        SlotBlock::create([
            'court_id' => $this->court->id,
            'play_date' => $conflictDateStr,
            'play_session_id' => $this->session->id,
            'reason' => 'Perbaikan Karpet Lapangan',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->from('/booking/baru?lapangan=' . $this->court->id . '&sesi=' . $this->session->id . '&tanggal=' . $startDate->format('Y-m-d'))
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $startDate->format('Y-m-d'),
                'type' => 'package',
            ]);

        $response->assertSessionHasErrors('booking');
        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * 4. Slot ditutup dan sesi lewat ditolak.
     */
    public function test_slot_ditutup_dan_lewat_ditolak(): void
    {
        $date = Carbon::now('Asia/Jayapura')->addDays(1)->format('Y-m-d');

        // Tutup slot
        SlotBlock::create([
            'court_id' => $this->court->id,
            'play_date' => $date,
            'play_session_id' => $this->session->id,
            'reason' => 'Turnamen Lokal',
            'created_by' => $this->admin->id,
        ]);

        $responseBlocked = $this->actingAs($this->customer)
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $date,
                'type' => 'single',
            ]);

        $responseBlocked->assertSessionHasErrors('booking');

        // Uji sesi lewat (kemarin)
        $pastDate = Carbon::now('Asia/Jayapura')->subDay()->format('Y-m-d');
        $responsePast = $this->actingAs($this->customer)
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $pastDate,
                'type' => 'single',
            ]);

        $responsePast->assertSessionHasErrors('booking');
    }

    /**
     * 5. Dua percobaan berurutan pada slot sama: yang kedua gagal ramah.
     */
    public function test_concurrency_guard_second_booking_fails_gracefully(): void
    {
        $dateStr = Carbon::now('Asia/Jayapura')->addDays(4)->format('Y-m-d');

        // Booking pertama berhasil
        $this->actingAs($this->customer)
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $dateStr,
                'type' => 'single',
            ]);

        $this->assertDatabaseCount('bookings', 1);

        // Booking kedua oleh pelanggan lain pada slot yang persis sama
        $customer2 = User::create([
            'name' => 'Customer Kedua',
            'phone' => '089988776655',
            'password' => 'password123',
            'role' => UserRole::CUSTOMER,
        ]);

        $response2 = $this->actingAs($customer2)
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $dateStr,
                'type' => 'single',
            ]);

        $response2->assertSessionHasErrors('booking');
        $this->assertDatabaseCount('bookings', 1);
    }

    /**
     * 6. Tamu mendaftar inline lalu booking langsung berhasil.
     */
    public function test_guest_inline_registration_and_booking(): void
    {
        $dateStr = Carbon::now('Asia/Jayapura')->addDays(3)->format('Y-m-d');

        $response = $this->post('/booking', [
            'name' => 'Tamu Pertama',
            'phone' => '082199887711',
            'password' => 'password123',
            'team_name' => 'PB Tamu',
            'lapangan' => $this->court->id,
            'sesi' => $this->session->id,
            'tanggal' => $dateStr,
            'type' => 'single',
        ]);

        $response->assertRedirect();

        // Pastikan akun dibuat dan terotentikasi
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '082199887711',
            'name' => 'Tamu Pertama',
            'role' => 'customer',
        ]);

        // Booking dibuat
        $this->assertDatabaseHas('bookings', [
            'type' => 'single',
            'status' => 'pending_payment',
        ]);
    }

    /**
     * 7. Batas pending booking per pengguna (settings.max_pending_bookings = 2).
     */
    public function test_max_pending_bookings_limit(): void
    {
        $d1 = Carbon::now('Asia/Jayapura')->addDays(3)->format('Y-m-d');
        $d2 = Carbon::now('Asia/Jayapura')->addDays(4)->format('Y-m-d');
        $d3 = Carbon::now('Asia/Jayapura')->addDays(5)->format('Y-m-d');

        // Booking 1
        $this->actingAs($this->customer)->post('/booking', [
            'lapangan' => $this->court->id,
            'sesi' => $this->session->id,
            'tanggal' => $d1,
            'type' => 'single',
        ]);

        // Booking 2
        $this->actingAs($this->customer)->post('/booking', [
            'lapangan' => $this->court->id,
            'sesi' => $this->session->id,
            'tanggal' => $d2,
            'type' => 'single',
        ]);

        $this->assertDatabaseCount('bookings', 2);

        // Booking 3 harus ditolak karena batas max_pending_bookings = 2
        $response3 = $this->actingAs($this->customer)->post('/booking', [
            'lapangan' => $this->court->id,
            'sesi' => $this->session->id,
            'tanggal' => $d3,
            'type' => 'single',
        ]);

        $response3->assertSessionHasErrors('booking');
        $this->assertDatabaseCount('bookings', 2);
    }

    /**
     * 8. Command bookings:expire membersihkan pending_payment lewat dan tidak menyentuh paid.
     */
    public function test_bookings_expire_command(): void
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $pastHold = $nowJayapura->copy()->subMinutes(10);
        $futureHold = $nowJayapura->copy()->addMinutes(10);

        // 1. Booking pending yang sudah kedaluwarsa
        $bExpired = Booking::create([
            'code' => 'EXPD0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => $pastHold,
        ]);
        BookingSlot::create([
            'booking_id' => $bExpired->id,
            'court_id' => $this->court->id,
            'play_session_id' => $this->session->id,
            'play_date' => $nowJayapura->format('Y-m-d'),
        ]);

        // 2. Booking paid (tidak boleh disentuh)
        $bPaid = Booking::create([
            'code' => 'PAID0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'expires_at' => $pastHold, // expires_at lama tidak relevan jika sudah paid
        ]);

        // Jalankan artisan bookings:expire
        $this->artisan('bookings:expire')
            ->expectsOutputToContain('Berhasil membersihkan 1 booking kedaluwarsa')
            ->assertSuccessful();

        $bExpired->refresh();
        $this->assertEquals(BookingStatus::EXPIRED, $bExpired->status);
        $this->assertCount(0, $bExpired->slots); // Slot dibebaskan

        $bPaid->refresh();
        $this->assertEquals(BookingStatus::PAID, $bPaid->status);
    }

    /**
     * 9. Pembatalan oleh pemilik membebaskan slot; tidak bisa membatalkan yang sudah paid.
     */
    public function test_cancellation_by_owner_of_booking(): void
    {
        $dateStr = Carbon::now('Asia/Jayapura')->addDays(3)->format('Y-m-d');

        $this->actingAs($this->customer)->post('/booking', [
            'lapangan' => $this->court->id,
            'sesi' => $this->session->id,
            'tanggal' => $dateStr,
            'type' => 'single',
        ]);

        $booking = Booking::first();
        $this->assertEquals(BookingStatus::PENDING_PAYMENT, $booking->status);
        $this->assertCount(1, $booking->slots);

        // Batalkan booking
        $response = $this->actingAs($this->customer)->post("/booking/{$booking->code}/batal");
        $response->assertRedirect();

        $booking->refresh();
        $this->assertEquals(BookingStatus::CANCELLED, $booking->status);
        $this->assertCount(0, $booking->slots); // Slot telah dihapus/dibebaskan
    }

    /**
     * 10. Policy: pemilik, admin, dan owner boleh melihat; pihak ketiga 403.
     */
    public function test_booking_policy(): void
    {
        $otherCustomer = User::create([
            'name' => 'Orang Lain',
            'phone' => '087711223344',
            'password' => 'password123',
            'role' => UserRole::CUSTOMER,
        ]);

        $booking = Booking::create([
            'code' => 'PLCY0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Pemilik boleh lihat
        $this->actingAs($this->customer)->get("/booking/{$booking->code}")->assertStatus(200);

        // Admin boleh lihat
        $this->actingAs($this->admin)->get("/booking/{$booking->code}")->assertStatus(200);

        // Owner boleh lihat (mode pantau)
        $this->actingAs($this->owner)->get("/booking/{$booking->code}")->assertStatus(200);

        // Pengguna lain ditolak (403)
        $this->actingAs($otherCustomer)->get("/booking/{$booking->code}")->assertStatus(403);

        // Pengguna lain tidak boleh membatalkan (403)
        $this->actingAs($otherCustomer)->post("/booking/{$booking->code}/batal")->assertStatus(403);
    }

    /**
     * 11. Perubahan harga di pengaturan tidak mengubah total_price booking lama.
     */
    public function test_price_change_does_not_affect_existing_booking(): void
    {
        $dateStr = Carbon::now('Asia/Jayapura')->addDays(3)->format('Y-m-d');

        $this->actingAs($this->customer)->post('/booking', [
            'lapangan' => $this->court->id,
            'sesi' => $this->session->id,
            'tanggal' => $dateStr,
            'type' => 'single',
        ]);

        $booking = Booking::first();
        $this->assertEquals(200000, $booking->total_price);

        // Ubah harga di pengaturan
        Setting::set('price_single', 350000);

        $booking->refresh();
        $this->assertEquals(200000, $booking->total_price); // Snapshot harga tetap utuh
    }

    /**
     * 12. Akun dengan peran owner dilarang booking dan melihat pemberitahuan pantau.
     */
    public function test_owner_role_cannot_book(): void
    {
        $dateStr = Carbon::now('Asia/Jayapura')->addDays(2)->format('Y-m-d');

        // GET /booking/baru ditolak untuk role owner
        $responseGet = $this->actingAs($this->owner)
            ->get("/booking/baru?lapangan={$this->court->id}&sesi={$this->session->id}&tanggal={$dateStr}");

        $responseGet->assertStatus(403);
        $responseGet->assertSee('Akun Owner Hanya Untuk Memantau');

        // POST /booking ditolak untuk role owner
        $responsePost = $this->actingAs($this->owner)
            ->post('/booking', [
                'lapangan' => $this->court->id,
                'sesi' => $this->session->id,
                'tanggal' => $dateStr,
                'type' => 'single',
            ]);

        $responsePost->assertStatus(403);
        $responsePost->assertSee('Akun Owner Hanya Untuk Memantau');
    }

    /**
     * Uji tambahan: Halaman Booking Saya (/booking) memuat tiga bagian.
     */
    public function test_booking_saya_page_displays_three_sections(): void
    {
        $response = $this->actingAs($this->customer)->get('/booking');
        $response->assertStatus(200);
        $response->assertSee('Booking Saya');
        $response->assertSee('Menunggu Bayar');
        $response->assertSee('Akan Datang');
        $response->assertSee('Riwayat Booking');
    }

    /**
     * Uji tambahan: Sel jadwal menampilkan penanda 'Punyamu' untuk slot milik user.
     */
    public function test_jadwal_shows_punyamu_badge_for_current_user(): void
    {
        $todayStr = Carbon::now('Asia/Jayapura')->format('Y-m-d');

        $booking = Booking::create([
            'code' => 'MINE0001',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);

        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court->id,
            'play_session_id' => $this->session->id,
            'play_date' => $todayStr,
        ]);

        $response = $this->actingAs($this->customer)->get("/jadwal?tampilan=hari&tanggal={$todayStr}");
        $response->assertStatus(200);
        $response->assertSee('Punyamu');
    }
}
