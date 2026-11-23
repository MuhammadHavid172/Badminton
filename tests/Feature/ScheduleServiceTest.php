<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\SlotBlock;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Court $court1;
    protected Court $court2;
    protected PlaySession $session1;
    protected PlaySession $session2;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->court1 = Court::create(['name' => 'Lapangan 1', 'sort_order' => 1]);
        $this->court2 = Court::create(['name' => 'Lapangan 2', 'sort_order' => 2]);

        $this->session1 = PlaySession::create([
            'label' => 'Sesi 1',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'sort_order' => 1,
        ]);
        $this->session2 = PlaySession::create([
            'label' => 'Sesi 2',
            'start_time' => '11:00:00',
            'end_time' => '15:00:00',
            'sort_order' => 2,
        ]);

        $this->user = User::create([
            'name' => 'Budi Santoso',
            'team_name' => 'PB Rajawali',
            'phone' => '081234567890',
            'password' => 'secret',
        ]);
    }

    /**
     * Uji prioritas status slot: 'ditutup' mengalahkan status booking lainnya.
     */
    public function test_slot_status_priority_ditutup_beats_other(): void
    {
        $service = new ScheduleService();
        $date = '2026-11-23';

        // Buat booking paid pada Lapangan 1 Sesi 1
        $booking = Booking::create([
            'code' => 'BOOK0010',
            'user_id' => $this->user->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);
        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $date,
        ]);

        // Buat slot block pada slot yang sama
        SlotBlock::create([
            'court_id' => $this->court1->id,
            'play_date' => $date,
            'play_session_id' => $this->session1->id,
            'reason' => 'Perawatan Lapangan',
            'created_by' => $this->user->id,
        ]);

        $now = Carbon::parse('2026-11-23 07:00:00', 'Asia/Jayapura');
        $grid = $service->dayGrid($date, $now);

        // Cari slot Lapangan 1 pada Sesi 1
        $slot = $grid['grid'][0]['courts'][0];
        $this->assertEquals('ditutup', $slot['status']);
        $this->assertEquals('Perawatan Lapangan', $slot['label']);
    }

    /**
     * Uji status 'terisi' menampilkan team_name atau name dan kategori Harian / Bulanan.
     */
    public function test_slot_status_paid_shows_customer_team_or_name_and_category(): void
    {
        $service = new ScheduleService();
        $date = '2026-11-23';

        $booking = Booking::create([
            'code' => 'BOOK0020',
            'user_id' => $this->user->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);
        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $date,
        ]);

        $now = Carbon::parse('2026-11-23 07:00:00', 'Asia/Jayapura');
        $grid = $service->dayGrid($date, $now);

        $slot = $grid['grid'][0]['courts'][0];
        $this->assertEquals('terisi', $slot['status']);
        $this->assertEquals('PB Rajawali', $slot['label']);
        $this->assertEquals('Harian', $slot['category']);
    }

    /**
     * Uji booking pending_payment yang telah kedaluwarsa dianggap kosong.
     */
    public function test_hold_expired_is_treated_as_kosong(): void
    {
        $service = new ScheduleService();
        $date = '2026-11-23';

        $booking = Booking::create([
            'code' => 'BOOK0030',
            'user_id' => $this->user->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => Carbon::parse('2026-11-23 06:45:00', 'Asia/Jayapura'),
        ]);
        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $date,
        ]);

        // Waktu acuan saat ini sudah melewati expires_at (jam 07.00 WIT)
        $now = Carbon::parse('2026-11-23 07:00:00', 'Asia/Jayapura');
        $grid = $service->dayGrid($date, $now);

        $slot = $grid['grid'][0]['courts'][0];
        $this->assertEquals('kosong', $slot['status']);
    }

    /**
     * Uji pending_payment yang masih berlaku berstatus 'menunggu_bayar' dan tanpa nama pemesan.
     */
    public function test_valid_pending_payment_is_menunggu_bayar_without_name(): void
    {
        $service = new ScheduleService();
        $date = '2026-11-23';

        $booking = Booking::create([
            'code' => 'BOOK0040',
            'user_id' => $this->user->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => Carbon::parse('2026-11-23 07:15:00', 'Asia/Jayapura'),
        ]);
        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $this->court1->id,
            'play_session_id' => $this->session1->id,
            'play_date' => $date,
        ]);

        $now = Carbon::parse('2026-11-23 07:05:00', 'Asia/Jayapura');
        $grid = $service->dayGrid($date, $now);

        $slot = $grid['grid'][0]['courts'][0];
        $this->assertEquals('menunggu_bayar', $slot['status']);
        $this->assertNull($slot['label']); // Menunggu bayar TIDAK menampilkan nama pemesan
    }

    /**
     * Uji sesi kosong yang telah lewat waktunya menjadi 'lewat'.
     */
    public function test_past_session_is_lewat(): void
    {
        $service = new ScheduleService();
        $date = '2026-11-23';

        // Sesi 1 berakhir jam 11:00 WIT. Waktu sekarang jam 11:30 WIT.
        $now = Carbon::parse('2026-11-23 11:30:00', 'Asia/Jayapura');
        $grid = $service->dayGrid($date, $now);

        $slot = $grid['grid'][0]['courts'][0];
        $this->assertEquals('lewat', $slot['status']);
    }

    /**
     * Uji efisiensi query (bebas N+1) pada dayGrid.
     */
    public function test_query_count_is_bounded(): void
    {
        $service = new ScheduleService();
        $date = '2026-11-23';

        // Catat jumlah kueri SQL yang dieksekusi
        DB::enableQueryLog();
        $service->dayGrid($date);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Query wajib terikat efisien (maksimal 6 query untuk courts, sessions, blocks, slots dengan eager load, dan renewals)
        $this->assertLessThanOrEqual(6, count($queries), 'Jumlah query dayGrid terdeteksi N+1: ' . count($queries));
    }
}
