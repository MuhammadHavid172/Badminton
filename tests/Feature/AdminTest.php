<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court1;
    protected Court $court2;
    protected PlaySession $session1;
    protected PlaySession $session2;
    protected User $customer;
    protected User $admin;
    protected User $owner;

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
            'name' => 'Pelanggan Biasa',
            'phone' => '081200000001',
            'email' => 'customer@test.com',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Operasional',
            'phone' => '081100000001',
            'email' => 'admin@test.com',
            'role' => UserRole::ADMIN,
            'password' => Hash::make('admin12345'),
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner Pantau',
            'phone' => '081100000004',
            'email' => 'owner@test.com',
            'role' => UserRole::OWNER,
            'password' => Hash::make('owner12345'),
            'is_active' => true,
        ]);
    }

    public function test_akses_halaman_admin_hanya_untuk_admin_customer_dan_owner_ditolak(): void
    {
        // Tamu -> redirect ke masuk
        $this->get(route('admin.dashboard'))->assertRedirect(route('masuk'));

        // Customer -> 403
        $this->actingAs($this->customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->customer)->get(route('admin.jatah-habis'))->assertForbidden();
        $this->actingAs($this->customer)->get(route('admin.booking.index'))->assertForbidden();

        // Owner -> 403 (owner hanya di area /owner)
        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('admin.booking-langsung'))->assertForbidden();

        // Admin -> 200
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.jatah-habis'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.booking.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.booking-langsung'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.tutup-slot'))->assertOk();
    }

    public function test_check_in_kehadiran_dan_batal_check_in_idempoten_dan_tercatat_log(): void
    {
        $todayStr = Carbon::now('Asia/Jayapura')->format('Y-m-d');

        $booking = Booking::create([
            'code' => 'TESTCKIN',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        $slot = BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $todayStr,
        ]);

        // 1. Admin check-in kehadiran
        $res = $this->actingAs($this->admin)->post(route('admin.slot.check-in', $slot->id), [
            'attendance_note' => 'Hadir tepat waktu bawa raket sendiri',
        ]);
        $res->assertSessionHas('status', 'Kehadiran berhasil dicatat.');

        $slot->refresh();
        $this->assertNotNull($slot->checked_in_at);
        $this->assertEquals($this->admin->id, $slot->checked_in_by);
        $this->assertEquals('Hadir tepat waktu bawa raket sendiri', $slot->attendance_note);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'slot.check_in',
            'user_id' => $this->admin->id,
            'subject_id' => $slot->id,
        ]);

        // 2. Admin batalkan check-in kehadiran
        $resCancel = $this->actingAs($this->admin)->post(route('admin.slot.batal-check-in', $slot->id));
        $resCancel->assertSessionHas('status', 'Catatan kehadiran berhasil dibatalkan.');

        $slot->refresh();
        $this->assertNull($slot->checked_in_at);
        $this->assertNull($slot->checked_in_by);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'slot.cancel_check_in',
            'user_id' => $this->admin->id,
            'subject_id' => $slot->id,
        ]);
    }

    public function test_pencarian_di_landing_hari_ini_dan_daftar_booking(): void
    {
        $todayStr = Carbon::now('Asia/Jayapura')->format('Y-m-d');

        $customCustomer = User::create([
            'name' => 'Agus Subroto',
            'phone' => '089988776655',
            'team_name' => 'PB Rajawali Sakti',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'code' => 'SEARCH01',
            'user_id' => $customCustomer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $todayStr,
        ]);

        // Cari berdasarkan kode
        $this->actingAs($this->admin)->get(route('admin.dashboard', ['q' => 'SEARCH01']))
            ->assertOk()
            ->assertSee('SEARCH01')
            ->assertSee('PB Rajawali Sakti');

        // Cari berdasarkan nomor HP
        $this->actingAs($this->admin)->get(route('admin.dashboard', ['q' => '089988776655']))
            ->assertOk()
            ->assertSee('Agus Subroto');

        // Filter di daftar booking
        $this->actingAs($this->admin)->get(route('admin.booking.index', ['status' => 'paid', 'q' => 'Rajawali']))
            ->assertOk()
            ->assertSee('SEARCH01');
    }

    public function test_halaman_jatah_habis_menampilkan_booking_bulanan_kritis_dan_nomor_hp_hanya_admin(): void
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $threeDaysAhead = $nowJayapura->copy()->addDays(3)->format('Y-m-d');

        $packageUser = User::create([
            'name' => 'Slamet Bulanan',
            'phone' => '085544332211',
            'team_name' => 'PB Kuda Mas',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $packageBooking = Booking::create([
            'code' => 'JATAH001',
            'user_id' => $packageUser->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
            'expires_at' => $nowJayapura->copy()->addMinutes(15),
        ]);

        // Pertemuan ke-4 (terakhir) berada 3 hari ke depan -> sisa 1 pertemuan
        // Pertemuan 1, 2, 3 sudah lewat
        BookingSlot::create([
            'booking_id' => $packageBooking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $nowJayapura->copy()->subDays(18)->format('Y-m-d'),
        ]);
        BookingSlot::create([
            'booking_id' => $packageBooking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $nowJayapura->copy()->subDays(11)->format('Y-m-d'),
        ]);
        BookingSlot::create([
            'booking_id' => $packageBooking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $nowJayapura->copy()->subDays(4)->format('Y-m-d'),
        ]);
        BookingSlot::create([
            'booking_id' => $packageBooking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $threeDaysAhead,
        ]);

        // Akses oleh Admin -> terlihat nomor HP dan teks pengingat
        $resAdmin = $this->actingAs($this->admin)->get(route('admin.jatah-habis'));
        $resAdmin->assertOk();
        $resAdmin->assertSee('JATAH001');
        $resAdmin->assertSee('085544332211');
        $resAdmin->assertSee('Slamet Bulanan');
        $resAdmin->assertSee('Salin Pesan Pengingat');

        // Akses Jadwal Publik JSON -> TIDAK BOLEH memuat nomor HP sama sekali
        $resPublic = $this->get('/api/jadwal/hari?tanggal=' . $threeDaysAhead);
        $resPublic->assertOk();
        $this->assertStringNotContainsString('085544332211', $resPublic->getContent());
    }

    public function test_perpanjang_atas_nama_tunai_lunas_dan_tahan_dulu(): void
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $startDate = $nowJayapura->copy()->addDays(2)->format('Y-m-d');

        $user = User::create([
            'name' => 'Wayan Bulanan',
            'phone' => '087711223344',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $origBooking = Booking::create([
            'code' => 'ORIG001',
            'user_id' => $user->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
            'expires_at' => $nowJayapura->copy()->addMinutes(15),
        ]);

        for ($i = 0; $i < 4; $i++) {
            BookingSlot::create([
                'booking_id' => $origBooking->id,
                'court_id' => $this->court1->id,
                'play_session_id' => $this->session1->id,
                'play_date' => Carbon::parse($startDate)->addWeeks($i)->format('Y-m-d'),
            ]);
        }

        // Perpanjang atas nama dengan metode tunai
        $res = $this->actingAs($this->admin)->post(route('admin.booking.perpanjang-atas-nama', $origBooking->code), [
            'metode' => 'tunai',
        ]);

        $newBooking = Booking::where('renewed_from_id', $origBooking->id)->first();
        $this->assertNotNull($newBooking);
        $this->assertEquals(BookingStatus::PAID, $newBooking->status);
        $this->assertEquals($this->admin->id, $newBooking->created_by);
        $this->assertCount(4, $newBooking->slots);

        $payment = Payment::where('booking_id', $newBooking->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('tunai', $payment->method);
        $this->assertEquals(PaymentStatus::PAID, $payment->status);
        $this->assertEquals($this->admin->id, $payment->recorded_by);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'booking.admin_renewed_cash',
            'subject_id' => $newBooking->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_booking_langsung_dengan_pelanggan_baru_dan_metode_tunai(): void
    {
        $todayStr = Carbon::now('Asia/Jayapura')->addDays(5)->format('Y-m-d');

        $res = $this->actingAs($this->admin)->post(route('admin.booking-langsung.store'), [
            'tipe_pelanggan' => 'baru',
            'nama_baru' => 'Pelanggan Baru Walkin',
            'hp_baru' => '0821-9876-5432',
            'team_name_baru' => 'PB Walkin',
            'lapangan' => $this->court2->id,
            'sesi' => $this->session2->id,
            'tanggal' => $todayStr,
            'type' => 'single',
            'metode_bayar' => 'tunai',
            'notes' => 'Pembayaran lunas di kasir',
        ]);

        $res->assertSessionHas('status', 'Booking langsung berhasil dibuat.');

        // User baru berhasil dibuat
        $newUser = User::where('phone', '082198765432')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Pelanggan Baru Walkin', $newUser->name);
        $this->assertEquals(UserRole::CUSTOMER, $newUser->role);

        // Booking lunas langsung
        $booking = Booking::where('user_id', $newUser->id)->latest()->first();
        $this->assertNotNull($booking);
        $this->assertEquals(BookingStatus::PAID, $booking->status);
        $this->assertEquals($this->admin->id, $booking->created_by);
        $this->assertEquals(200000, $booking->total_price);

        $payment = Payment::where('booking_id', $booking->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('tunai', $payment->method);
        $this->assertEquals(PaymentStatus::PAID, $payment->status);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'booking.direct_cash',
            'subject_id' => $booking->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_tutup_slot_berhasil_dan_ditolak_jika_ada_booking_aktif(): void
    {
        $targetDate = Carbon::now('Asia/Jayapura')->addDays(7)->format('Y-m-d');

        // 1. Berhasil tutup slot kosong
        $res = $this->actingAs($this->admin)->post(route('admin.tutup-slot.store'), [
            'court_id' => $this->court1->id,
            'play_date' => $targetDate,
            'play_session_id' => $this->session1->id,
            'reason' => 'Perbaikan net lapangan',
        ]);
        $res->assertSessionHas('status', 'Penutupan slot berhasil disimpan.');

        $block = SlotBlock::where('court_id', $this->court1->id)->where('play_date', $targetDate)->first();
        $this->assertNotNull($block);
        $this->assertEquals('Perbaikan net lapangan', $block->reason);

        // 2. Buka kembali slot
        $delRes = $this->actingAs($this->admin)->delete(route('admin.tutup-slot.destroy', $block->id));
        $delRes->assertSessionHas('status', 'Slot berhasil dibuka kembali.');
        $this->assertDatabaseMissing('slot_blocks', ['id' => $block->id]);

        // 3. Ada booking aktif pada slot tersebut -> penutupan ditolak
        $activeBooking = Booking::create([
            'code' => 'ACTIVE01',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);
        BookingSlot::create([
            'booking_id' => $activeBooking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $targetDate,
        ]);

        $failRes = $this->actingAs($this->admin)->post(route('admin.tutup-slot.store'), [
            'court_id' => $this->court1->id,
            'play_date' => $targetDate,
            'play_session_id' => $this->session1->id,
            'reason' => 'Perbaikan darurat',
        ]);
        $failRes->assertSessionHasErrors('court_id');
    }

    public function test_admin_batalkan_booking_membebaskan_slot_masa_depan_riwayat_tetap_dan_kirim_notifikasi(): void
    {
        Notification::fake();

        $nowJayapura = Carbon::now('Asia/Jayapura');
        $pastDate = $nowJayapura->copy()->subDays(7)->format('Y-m-d');
        $futureDate = $nowJayapura->copy()->addDays(7)->format('Y-m-d');

        $booking = Booking::create([
            'code' => 'CANCEL01',
            'user_id' => $this->customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 600000,
            'expires_at' => $nowJayapura->copy()->addMinutes(15),
        ]);

        $pastSlot = BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $pastDate,
            'checked_in_at' => $nowJayapura->copy()->subDays(7),
        ]);

        $futureSlot = BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $futureDate,
        ]);

        $res = $this->actingAs($this->admin)->post(route('admin.booking.batal', $booking->code), [
            'reason' => 'Pelanggan membatalkan sewa paket',
            'refund_note' => 'Tidak ada pengembalian dana',
        ]);

        $booking->refresh();
        $this->assertEquals(BookingStatus::CANCELLED, $booking->status);
        $this->assertEquals('Pelanggan membatalkan sewa paket', $booking->cancel_reason);
        $this->assertEquals($this->admin->id, $booking->cancelled_by);

        // Slot masa depan telah dihapus/dibebaskan
        $this->assertDatabaseMissing('booking_slots', ['id' => $futureSlot->id]);

        // Slot masa lalu tetap dipertahankan sebagai riwayat
        $this->assertDatabaseHas('booking_slots', ['id' => $pastSlot->id]);

        // Notifikasi pembatalan terkirim ke customer
        Notification::assertSentTo($this->customer, BookingCancelledNotification::class);
    }

    public function test_admin_batalkan_booking_berbayar_memerlukan_konfirmasi_password(): void
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $futureDate = $nowJayapura->copy()->addDays(2)->format('Y-m-d');

        $paidBooking = Booking::create([
            'code' => 'PAIDCAN0',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'expires_at' => $nowJayapura->copy()->addMinutes(15),
        ]);

        BookingSlot::create([
            'booking_id' => $paidBooking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $futureDate,
        ]);

        // 1. Tanpa password atau password salah -> gagal validasi
        $failRes = $this->actingAs($this->admin)->post(route('admin.booking.batal', $paidBooking->code), [
            'reason' => 'Perbaikan lapangan',
            'password' => 'passwordsalah',
        ]);
        $failRes->assertSessionHasErrors('password');

        // 2. Dengan password benar ('admin12345') -> berhasil
        $successRes = $this->actingAs($this->admin)->post(route('admin.booking.batal', $paidBooking->code), [
            'reason' => 'Perbaikan lapangan',
            'password' => 'admin12345',
            'refund_note' => 'Dikembalikan tunai',
        ]);
        $paidBooking->refresh();
        $this->assertEquals(BookingStatus::CANCELLED, $paidBooking->status);
    }

    public function test_ekspor_csv_aman_dari_csv_injection(): void
    {
        $maliciousCustomer = User::create([
            'name' => '=SUM(A1:A10)',
            'phone' => '+628129999999',
            'team_name' => '@HackerTeam',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'code' => '-INJECT0',
            'user_id' => $maliciousCustomer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'expires_at' => Carbon::now('Asia/Jayapura')->addMinutes(15),
        ]);

        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => Carbon::now('Asia/Jayapura')->format('Y-m-d'),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.booking.ekspor-csv'));
        $res->assertOk();
        $res->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        // Stream output dievaluasi
        ob_start();
        $res->sendContent();
        $csvContent = ob_get_clean();

        // Seluruh karakter bahaya (=, +, -, @) harus diawali tanda petik tunggal '
        $this->assertStringContainsString("'-INJECT0", $csvContent);
        $this->assertStringContainsString("'=SUM(A1:A10)", $csvContent);
        $this->assertStringContainsString("'+628129999999", $csvContent);
        $this->assertStringContainsString("'@HackerTeam", $csvContent);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'booking.csv_export',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_enforce_idle_timeout_logout_admin_setelah_idle_melebihi_batas(): void
    {
        // Simulasi aktivitas terakhir 61 menit yang lalu
        $timeoutMinutes = config('gor.idle_timeout_minutes', 60);
        $lastActivityTimestamp = now()->subMinutes($timeoutMinutes + 1)->timestamp;

        $res = $this->actingAs($this->admin)
            ->withSession(['admin_last_activity_time' => $lastActivityTimestamp])
            ->get(route('admin.dashboard'));

        // Admin logout otomatis dan dialihkan ke login
        $res->assertRedirect(route('masuk'));
        $this->assertGuest();
        $res->assertSessionHas('error');
    }
}
