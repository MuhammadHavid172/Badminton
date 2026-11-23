<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\User;
use App\Services\PackageLifecycle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Court $court;
    protected PlaySession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Paket User',
            'phone' => '081299990001',
            'password' => 'secret',
        ]);

        $this->court = Court::create(['name' => 'Lapangan 1', 'sort_order' => 1]);
        $this->session = PlaySession::create([
            'label' => 'Sesi 4',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'sort_order' => 4,
        ]);

        Setting::set('renew_notice_remaining', 1);
    }

    /**
     * Uji total pertemuan dan slot pertemuan terakhir.
     */
    public function test_total_meetings_and_last_slot(): void
    {
        $booking = Booking::create([
            'code' => 'PKG00001',
            'user_id' => $this->user->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        $dates = ['2026-11-02', '2026-11-09', '2026-11-16', '2026-11-23'];
        foreach ($dates as $date) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court->id,
                'play_session_id' => $this->session->id,
                'play_date' => $date,
            ]);
        }

        $booking->load(['slots.playSession']);
        $lifecycle = new PackageLifecycle($booking);

        $this->assertTrue($lifecycle->isValidPackage());
        $this->assertEquals(4, $lifecycle->totalMeetings());
        $this->assertEquals('2026-11-23', $lifecycle->lastPlayDate());
        $this->assertEquals(1, $lifecycle->meetingNumberForDate('2026-11-02'));
        $this->assertEquals(4, $lifecycle->meetingNumberForDate('2026-11-23'));
    }

    /**
     * Uji sisa pertemuan menurut zona waktu Asia/Jayapura.
     */
    public function test_remaining_meetings_jayapura(): void
    {
        $booking = Booking::create([
            'code' => 'PKG00002',
            'user_id' => $this->user->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        $dates = ['2026-11-02', '2026-11-09', '2026-11-16', '2026-11-23'];
        foreach ($dates as $date) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court->id,
                'play_session_id' => $this->session->id,
                'play_date' => $date,
            ]);
        }

        $booking->load(['slots.playSession']);
        $lifecycle = new PackageLifecycle($booking);

        // Kasus 1: Waktu saat ini tanggal 2026-11-20 (pertemuan 1, 2, 3 sudah lewat; pertemuan 4 belum selesai)
        $now = Carbon::parse('2026-11-20 12:00:00', 'Asia/Jayapura');
        $this->assertEquals(1, $lifecycle->remainingMeetings($now));

        // Kasus 2: Waktu saat ini sebelum seluruh pertemuan (misal 2026-11-01)
        $nowBefore = Carbon::parse('2026-11-01 10:00:00', 'Asia/Jayapura');
        $this->assertEquals(4, $lifecycle->remainingMeetings($nowBefore));

        // Kasus 3: Waktu saat ini setelah seluruh pertemuan selesai (misal 2026-11-24)
        $nowAfter = Carbon::parse('2026-11-24 00:00:00', 'Asia/Jayapura');
        $this->assertEquals(0, $lifecycle->remainingMeetings($nowAfter));
    }

    /**
     * Uji penanda 'Sisa 1x' muncul saat tersisa 1 pertemuan dan HILANG setelah diperpanjang.
     */
    public function test_sisa_1x_badge_presence_and_removal(): void
    {
        $booking = Booking::create([
            'code' => 'PKG00003',
            'user_id' => $this->user->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        $dates = ['2026-11-02', '2026-11-09', '2026-11-16', '2026-11-23'];
        foreach ($dates as $date) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court->id,
                'play_session_id' => $this->session->id,
                'play_date' => $date,
            ]);
        }

        $booking->load(['slots.playSession']);
        $lifecycle = new PackageLifecycle($booking);

        // Saat pertemuan tersisa 1 dan belum diperpanjang
        $now = Carbon::parse('2026-11-20 12:00:00', 'Asia/Jayapura');
        $this->assertEquals('belum_lanjut', $lifecycle->continuationStatus($now));
        $this->assertTrue($lifecycle->shouldShowRemainingOneBadge($now));
        $this->assertEquals('hampir_habis', $lifecycle->state($now));

        // Buat booking perpanjangan berstatus paid
        Booking::create([
            'code' => 'PKG00004',
            'user_id' => $this->user->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
            'renewed_from_id' => $booking->id,
        ]);

        // Setelah perpanjangan dibayar, penanda 'Sisa 1x' WAJIB HILANG
        $lifecycleAfterRenewal = new PackageLifecycle($booking);
        $this->assertEquals('sudah_lanjut', $lifecycleAfterRenewal->continuationStatus($now));
        $this->assertFalse($lifecycleAfterRenewal->shouldShowRemainingOneBadge($now));
    }

    /**
     * Uji state terakhir_hari_ini saat pertemuan terakhir jatuh pada hari ini.
     */
    public function test_state_terakhir_hari_ini(): void
    {
        $booking = Booking::create([
            'code' => 'PKG00005',
            'user_id' => $this->user->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
        ]);

        $dates = ['2026-11-02', '2026-11-09', '2026-11-16', '2026-11-23'];
        foreach ($dates as $date) {
            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $this->court->id,
                'play_session_id' => $this->session->id,
                'play_date' => $date,
            ]);
        }

        $booking->load(['slots.playSession']);
        $lifecycle = new PackageLifecycle($booking);

        // Hari ini adalah 23 November 2026 jam 14.00 WIT (sebelum sesi jam 19.00-23.00 berakhir)
        $now = Carbon::parse('2026-11-23 14:00:00', 'Asia/Jayapura');
        $this->assertEquals('terakhir_hari_ini', $lifecycle->state($now));

        // Setelah jam 23.00 WIT, pertemuan selesai
        $nowFinished = Carbon::parse('2026-11-23 23:30:00', 'Asia/Jayapura');
        $this->assertEquals('selesai', $lifecycle->state($nowFinished));
    }
}
