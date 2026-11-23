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
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementAndOwnerTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court1;
    protected Court $court2;
    protected PlaySession $session1;
    protected PlaySession $session2;
    protected User $admin;
    protected User $admin2;
    protected User $owner;
    protected User $customer;

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
        Setting::set('max_pending_bookings', 2);
        Setting::set('renew_notice_remaining', 1);
        Setting::set('last_day_notice_hour', 6);

        $this->admin = User::create([
            'name' => 'Admin Utama',
            'phone' => '081100000001',
            'email' => 'admin@gor.test',
            'role' => UserRole::ADMIN,
            'password' => Hash::make('admin12345'),
            'is_active' => true,
        ]);

        $this->admin2 = User::create([
            'name' => 'Admin Kedua',
            'phone' => '081100000002',
            'email' => 'admin2@gor.test',
            'role' => UserRole::ADMIN,
            'password' => Hash::make('admin12345'),
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Pemilik GOR',
            'phone' => '081100000004',
            'email' => 'owner@gor.test',
            'role' => UserRole::OWNER,
            'password' => Hash::make('owner12345'),
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Pelanggan PB',
            'phone' => '081200000001',
            'email' => 'customer@pb.test',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }

    /**
     * Test matriks hak akses 3 peran:
     * - Customer tidak bisa membuka /admin/* maupun /owner/* (403)
     * - Owner tidak bisa membuka /admin/* (403)
     * - Owner bisa GET semua halaman /owner/* (200)
     * - Owner mendapat 403 pada semua route mutasi
     * - Admin bisa membuka /admin/*
     */
    public function test_matriks_hak_akses_tiga_peran_dan_audit_rute(): void
    {
        // 1. Customer dilarang mengakses admin dan owner
        $this->actingAs($this->customer);
        $this->get('/admin/ringkasan')->assertStatus(403);
        $this->get('/admin/pengguna')->assertStatus(403);
        $this->get('/owner')->assertStatus(403);
        $this->get('/owner/ringkasan')->assertStatus(403);

        // 2. Owner dilarang membuka /admin/*
        $this->actingAs($this->owner);
        $this->get('/admin')->assertStatus(403);
        $this->get('/admin/ringkasan')->assertStatus(403);
        $this->get('/admin/pengguna')->assertStatus(403);

        // 3. Owner bisa mengakses semua GET /owner/*
        $this->get('/owner')->assertStatus(200);
        $this->get('/owner/ringkasan')->assertStatus(200);
        $this->get('/owner/hari-ini')->assertStatus(200);
        $this->get('/owner/jatah-habis')->assertStatus(200);
        $this->get('/owner/booking')->assertStatus(200);
        $this->get('/owner/pengguna')->assertStatus(200);
        $this->get('/owner/pengguna/' . $this->customer->id)->assertStatus(200);
        $this->get('/owner/aktivitas')->assertStatus(200);
        $this->get('/owner/laporan')->assertStatus(200);
        $this->get('/owner/laporan/ekspor-csv')->assertStatus(200);
        $this->get('/owner/pengaturan')->assertStatus(200);

        // 4. Owner dilarang keras melakukan SEMUA aksi mutasi (403)
        $this->post('/admin/pengguna', [
            'name' => 'Baru',
            'phone' => '081299999999',
            'role' => 'customer',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(403);

        $this->put('/admin/pengguna/' . $this->customer->id . '/peran', [
            'role' => 'admin',
            'password' => 'owner12345',
        ])->assertStatus(403);

        $this->put('/admin/pengguna/' . $this->customer->id . '/toggle-status', [
            'password' => 'owner12345',
        ])->assertStatus(403);

        $this->post('/admin/pengguna/' . $this->customer->id . '/reset-password', [
            'password_admin' => 'owner12345',
        ])->assertStatus(403);

        $this->put('/admin/pengaturan', [
            'price_single' => 250000,
            'price_package' => 700000,
            'hold_minutes' => 15,
            'max_pending_bookings' => 2,
            'renew_notice_remaining' => 1,
            'last_day_notice_hour' => 6,
            'password' => 'owner12345',
        ])->assertStatus(403);

        $this->post('/admin/booking-langsung', [
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => Carbon::now('Asia/Jayapura')->addDays(1)->toDateString(),
            'category' => 'single',
            'customer_mode' => 'existing',
            'existing_user_id' => $this->customer->id,
            'payment_action' => 'paid_cash',
        ])->assertStatus(403);

        // 5. Admin bisa mengakses halaman admin
        $this->actingAs($this->admin);
        $this->get('/admin/ringkasan')->assertStatus(200);
        $this->get('/admin/pengguna')->assertStatus(200);
        $this->get('/admin/aktivitas')->assertStatus(200);
        $this->get('/admin/laporan')->assertStatus(200);
        $this->get('/admin/pengaturan')->assertStatus(200);
    }

    /**
     * Test mode pantau: View tidak merender form atau tombol mutasi.
     */
    public function test_halaman_mode_pantau_owner_tidak_memuat_tombol_atau_form_mutasi(): void
    {
        $this->actingAs($this->owner);

        // Halaman Ringkasan Mode Pantau memuat chip OWNER MODE PANTAU
        $respRingkasan = $this->get('/owner/ringkasan');
        $respRingkasan->assertStatus(200);
        $respRingkasan->assertSee('MODE PANTAU');
        $respRingkasan->assertSee('OWNER');

        // Halaman Hari Ini Mode Pantau: tidak ada tombol check-in atau form mutasi
        $respHariIni = $this->get('/owner/hari-ini');
        $respHariIni->assertStatus(200);
        $respHariIni->assertDontSee('Sudah hadir');
        $respHariIni->assertDontSee('Batal hadir');
        $respHariIni->assertDontSee('admin.slot.check-in');

        // Halaman Pengguna Mode Pantau: tidak ada form Tambah Pengguna
        $respPengguna = $this->get('/owner/pengguna');
        $respPengguna->assertStatus(200);
        $respPengguna->assertDontSee('Tambah Pengguna Baru');
        $respPengguna->assertDontSee('action="' . route('admin.pengguna.store') . '"', false);

        // Halaman Detail Pengguna Mode Pantau: tidak ada tombol Ubah Peran, Nonaktifkan, Reset Password
        $respPenggunaShow = $this->get('/owner/pengguna/' . $this->customer->id);
        $respPenggunaShow->assertStatus(200);
        $respPenggunaShow->assertDontSee('Ubah Peran');
        $respPenggunaShow->assertDontSee('Atur Ulang Password');
        $respPenggunaShow->assertDontSee('Nonaktifkan');

        // Halaman Pengaturan Mode Pantau: tidak ada tombol simpan pengaturan, ada tanda mode pantau
        $respPengaturan = $this->get('/owner/pengaturan');
        $respPengaturan->assertStatus(200);
        $respPengaturan->assertSee('MODE PANTAU');
        $respPengaturan->assertDontSee('Simpan Perubahan Pengaturan');
        $respPengaturan->assertDontSee('name="password"', false);
    }

    /**
     * Test admin tidak boleh menonaktifkan atau menurunkan peran dirinya sendiri.
     */
    public function test_admin_tidak_bisa_menonaktifkan_atau_menurunkan_diri_sendiri(): void
    {
        $this->actingAs($this->admin);

        // 1. Coba turunkan peran diri sendiri
        $respPeran = $this->put('/admin/pengguna/' . $this->admin->id . '/peran', [
            'role' => 'customer',
            'password' => 'admin12345',
        ]);
        $respPeran->assertSessionHasErrors('role');
        $this->assertEquals(UserRole::ADMIN, $this->admin->fresh()->role);

        // 2. Coba nonaktifkan diri sendiri
        $respStatus = $this->put('/admin/pengguna/' . $this->admin->id . '/toggle-status', [
            'password' => 'admin12345',
        ]);
        $respStatus->assertSessionHasErrors('status');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    /**
     * Test admin terakhir di sistem tidak bisa dinonaktifkan atau diturunkan.
     */
    public function test_admin_terakhir_tidak_bisa_dinonaktifkan_atau_diturunkan(): void
    {
        // Nonaktifkan admin2 terlebih dahulu sehingga hanya admin utama yang aktif
        $this->admin2->update(['is_active' => false]);
        $this->assertEquals(1, User::where('role', UserRole::ADMIN)->where('is_active', true)->count());

        $this->actingAs($this->admin);

        // Buat admin ketiga untuk mencoba menurunkan akun admin yang aktif
        $admin3 = User::create([
            'name' => 'Admin Tiga',
            'phone' => '081100000003',
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Hapus admin2 dan jadikan admin3 satu-satunya selain admin utama
        $this->admin2->delete();

        // Sekarang kita coba nonaktifkan admin3 ketika admin3 adalah satu-satunya admin aktif lainnya
        // lalu kita coba jika hanya tersisa 1 admin aktif
        $admin3->update(['is_active' => false]);
        $this->assertEquals(1, User::where('role', UserRole::ADMIN)->where('is_active', true)->count());

        // Login sebagai admin3 (jika diaktifkan sementara) atau coba ubah admin utama
        $admin3->update(['is_active' => true]);
        // Sekarang ada 2: admin & admin3. Login sebagai admin, ubah admin3 jadi customer: berhasil.
        $this->put('/admin/pengguna/' . $admin3->id . '/peran', [
            'role' => 'customer',
            'password' => 'admin12345',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(UserRole::CUSTOMER, $admin3->fresh()->role);

        // Sekarang hanya ada 1 admin aktif yaitu $this->admin.
        // Jika akun lain mencoba menurunkan admin utama (misal admin3 coba login), tidak bisa.
        // Dan jika dicoba menurunkan admin ketika jumlah admin aktif = 1:
        $this->actingAs($this->admin);
        $resp = $this->put('/admin/pengguna/' . $this->admin->id . '/peran', [
            'role' => 'customer',
            'password' => 'admin12345',
        ]);
        $resp->assertSessionHasErrors('role');
    }

    /**
     * Test admin bisa menambah pengguna baru (customer, admin, owner) dengan normalisasi nomor HP dan log aktivitas.
     */
    public function test_admin_bisa_menambah_pengguna_baru(): void
    {
        $this->actingAs($this->admin);

        $resp = $this->post('/admin/pengguna', [
            'name' => 'Budi Santoso',
            'phone' => '+62 813-1234-5678',
            'email' => 'budi@example.com',
            'team_name' => 'PB Rajawali',
            'role' => 'customer',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $resp->assertRedirect(route('admin.pengguna.index'));
        $resp->assertSessionHas('status');

        $user = User::where('phone', '081312345678')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Budi Santoso', $user->name);
        $this->assertEquals('PB Rajawali', $user->team_name);
        $this->assertEquals(UserRole::CUSTOMER, $user->role);
        $this->assertTrue($user->is_active);

        // Cek log aktivitas
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.created',
            'subject_id' => $user->id,
        ]);
    }

    /**
     * Test admin bisa mengubah peran pengguna, menonaktifkan pengguna, dan mereset password dengan konfirmasi password admin.
     */
    public function test_admin_kelola_pengguna_dan_reset_password_dengan_konfirmasi(): void
    {
        $this->actingAs($this->admin);

        // 1. Ubah Peran gagal jika password konfirmasi salah
        $respPeranSalah = $this->put('/admin/pengguna/' . $this->customer->id . '/peran', [
            'role' => 'admin',
            'password' => 'passwordsalah',
        ]);
        $respPeranSalah->assertSessionHasErrors('password');

        // 2. Ubah Peran berhasil dengan password benar
        $respPeranBenar = $this->put('/admin/pengguna/' . $this->customer->id . '/peran', [
            'role' => 'admin',
            'password' => 'admin12345',
        ]);
        $respPeranBenar->assertSessionHasNoErrors();
        $this->assertEquals(UserRole::ADMIN, $this->customer->fresh()->role);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.role_changed',
            'subject_id' => $this->customer->id,
        ]);

        // 3. Toggle Status Pengguna
        $respToggle = $this->put('/admin/pengguna/' . $this->customer->id . '/toggle-status', [
            'password' => 'admin12345',
        ]);
        $respToggle->assertSessionHasNoErrors();
        $this->assertFalse($this->customer->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.status_toggled',
            'subject_id' => $this->customer->id,
        ]);

        // 4. Reset Password Pengguna
        $respReset = $this->post('/admin/pengguna/' . $this->customer->id . '/reset-password', [
            'password_admin' => 'admin12345',
            'password_baru' => 'passBaru123',
        ]);
        $respReset->assertSessionHasNoErrors();
        $respReset->assertSessionHas('status');

        $this->assertTrue(Hash::check('passBaru123', $this->customer->fresh()->password));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.password_reset',
            'subject_id' => $this->customer->id,
        ]);
    }

    /**
     * Test perhitungan ringkasan pendapatan, okupansi, dan grafik 29 hari di DashboardService.
     */
    public function test_perhitungan_ringkasan_dan_grafik_okupansi_zona_jayapura(): void
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->toDateString();

        // Buat booking paid hari ini
        $booking = Booking::create([
            'code' => 'TESTOKUP',
            'user_id' => $this->customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);

        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $todayStr,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method' => 'simulated',
            'provider' => 'simulated',
            'amount' => 200000,
            'status' => PaymentStatus::PAID,
            'paid_at' => $nowJayapura->toDateTimeString(),
        ]);

        $dashboardService = app(DashboardService::class);
        $stats = $dashboardService->getSummaryStats($nowJayapura);

        $this->assertEquals(200000, $stats['pendapatan_hari_ini']);
        $this->assertGreaterThanOrEqual(200000, $stats['pendapatan_bulan_ini']);
        $this->assertGreaterThanOrEqual(1, $stats['booking_hari_ini']);
        $this->assertGreaterThan(0, $stats['okupansi_hari_ini_persen']);

        $chartDays = $dashboardService->getOccupancyChartData($nowJayapura);
        $this->assertCount(29, $chartDays);

        $todayPoint = collect($chartDays)->firstWhere('is_today', true);
        $this->assertNotNull($todayPoint);
        $this->assertEquals($todayStr, $todayPoint['date']);
        $this->assertGreaterThanOrEqual(1, $todayPoint['occupied']);
    }

    /**
     * Test laporan pendapatan, kategori, metode bayar, dan rasio perpanjangan bulanan.
     */
    public function test_laporan_operasional_dan_rasio_perpanjangan_bulanan(): void
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->toDateString();

        // 1. Buat paket bulanan yang sudah berakhir
        $oldPackage = Booking::create([
            'code' => 'PKGOLD01',
            'user_id' => $this->customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        for ($i = 0; $i < 4; $i++) {
            BookingSlot::create([
                'booking_id' => $oldPackage->id,
                'court_id' => $this->court1->id,
                'play_session_id' => $this->session1->id,
                'play_date' => $nowJayapura->copy()->subDays(28 - ($i * 7))->toDateString(),
            ]);
        }

        // 2. Buat paket bulanan perpanjangan (renewed_from_id)
        $newPackage = Booking::create([
            'code' => 'PKGNEW01',
            'user_id' => $this->customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
            'renewed_from_id' => $oldPackage->id,
        ]);

        for ($i = 0; $i < 4; $i++) {
            BookingSlot::create([
                'booking_id' => $newPackage->id,
                'court_id' => $this->court1->id,
                'play_session_id' => $this->session1->id,
                'play_date' => $nowJayapura->copy()->addDays(7 + ($i * 7))->toDateString(),
            ]);
        }

        $dashboardService = app(DashboardService::class);
        $reportData = $dashboardService->getReportData(
            $nowJayapura->copy()->subDays(60)->toDateString(),
            $nowJayapura->toDateString(),
            $nowJayapura
        );

        $this->assertArrayHasKey('renewal_rate_percent', $reportData);
        $this->assertEquals(1, $reportData['total_ended_bulanan']);
        $this->assertEquals(1, $reportData['total_renewed_bulanan']);
        $this->assertEquals(100, $reportData['renewal_rate_percent']);
    }

    /**
     * Test ekspor CSV laporan aman dari formula injection dan mencatat log aktivitas.
     */
    public function test_ekspor_csv_laporan_aman_dari_csv_injection(): void
    {
        $this->actingAs($this->admin);

        // Buat user dengan nama berpotensi CSV Injection
        $dangerousUser = User::create([
            'name' => '=cmd|"/C calc"!A0',
            'phone' => '081299998888',
            'role' => UserRole::CUSTOMER,
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'code' => '+INJECT1',
            'user_id' => $dangerousUser->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method' => 'simulated',
            'provider' => 'simulated',
            'amount' => 200000,
            'status' => PaymentStatus::PAID,
            'paid_at' => Carbon::now('Asia/Jayapura')->toDateTimeString(),
        ]);

        $response = $this->get('/admin/laporan/ekspor-csv');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();

        // Karakter =, + harus diawali dengan tanda petik tunggal '
        $this->assertStringContainsString("'=cmd", $content);
        $this->assertStringContainsString("'+INJECT1", $content);

        // Verifikasi log tercatat
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'laporan.csv_export',
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * Test pembaruan pengaturan sistem memerlukan konfirmasi password dan mencatat log dengan nilai lama dan baru.
     */
    public function test_update_pengaturan_memerlukan_konfirmasi_password_dan_tercatat_di_log(): void
    {
        $this->actingAs($this->admin);

        // 1. Password salah ditolak
        $respSalah = $this->put('/admin/pengaturan', [
            'price_single' => 250000,
            'price_package' => 700000,
            'hold_minutes' => 20,
            'max_pending_bookings' => 3,
            'renew_notice_remaining' => 2,
            'last_day_notice_hour' => 7,
            'password' => 'passwordsalah',
        ]);
        $respSalah->assertSessionHasErrors('password');

        // 2. Berhasil dengan password benar
        $respBenar = $this->put('/admin/pengaturan', [
            'price_single' => 250000,
            'price_package' => 700000,
            'hold_minutes' => 20,
            'max_pending_bookings' => 3,
            'renew_notice_remaining' => 2,
            'last_day_notice_hour' => 7,
            'password' => 'admin12345',
        ]);
        $respBenar->assertRedirect(route('admin.pengaturan'));
        $respBenar->assertSessionHas('status');

        $this->assertEquals(250000, Setting::getInt('price_single'));
        $this->assertEquals(700000, Setting::getInt('price_package'));
        $this->assertEquals(20, Setting::getInt('hold_minutes'));

        // Cek log aktivitas dengan old dan new
        $log = ActivityLog::where('action', 'setting.updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals(200000, $log->meta['old']['price_single']);
        $this->assertEquals(250000, $log->meta['new']['price_single']);
    }
}
