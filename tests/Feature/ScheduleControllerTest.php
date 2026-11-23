<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Court::create(['name' => 'Lapangan 1', 'sort_order' => 1]);
        PlaySession::create([
            'label' => 'Sesi 1',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'sort_order' => 1,
        ]);
    }

    /**
     * Uji halaman /jadwal mengembalikan 200 untuk tampilan 'hari'.
     */
    public function test_jadwal_page_day_view_returns_200(): void
    {
        $response = $this->get('/jadwal?tampilan=hari');
        $response->assertStatus(200);
        $response->assertSee('Jadwal Lapangan');
        $response->assertSee('Per hari');
        $response->assertSee('Kalender');
    }

    /**
     * Uji halaman /jadwal mengembalikan 200 untuk tampilan 'kalender'.
     */
    public function test_jadwal_page_calendar_view_returns_200(): void
    {
        $response = $this->get('/jadwal?tampilan=kalender');
        $response->assertStatus(200);
        $response->assertSee('Jadwal Lapangan');
        $response->assertSee('Bulan Sebelumnya');
        $response->assertSee('Bulan Berikutnya');
    }

    /**
     * Uji tanggal tidak valid dialihkan ke hari ini.
     */
    public function test_invalid_date_redirects_to_today(): void
    {
        $response = $this->get('/jadwal?tampilan=hari&tanggal=invalid-date');
        $response->assertRedirect();

        $todayStr = Carbon::now('Asia/Jayapura')->format('Y-m-d');
        $response->assertRedirectContains("tanggal={$todayStr}");
    }

    /**
     * Uji tanggal di luar batas rentang (1 bulan ke belakang s/d 6 bulan ke depan) dialihkan ke hari ini.
     */
    public function test_out_of_range_date_redirects_to_today(): void
    {
        // 1 tahun ke belakang
        $pastDate = Carbon::now('Asia/Jayapura')->subYear()->format('Y-m-d');
        $responsePast = $this->get("/jadwal?tampilan=hari&tanggal={$pastDate}");
        $responsePast->assertRedirect();

        // 2 tahun ke depan
        $futureDate = Carbon::now('Asia/Jayapura')->addYears(2)->format('Y-m-d');
        $responseFuture = $this->get("/jadwal?tampilan=hari&tanggal={$futureDate}");
        $responseFuture->assertRedirect();
    }

    /**
     * Uji API JSON /api/jadwal/hari mengembalikan struktur data slot TANPA info privat pengguna.
     */
    public function test_api_day_endpoint_returns_json_without_personal_info(): void
    {
        $user = User::create([
            'name' => 'Pemesan Privat',
            'team_name' => 'PB Rahasia',
            'phone' => '081234567899',
            'email' => 'privat@test.com',
            'password' => 'secret',
        ]);

        $court = Court::first();
        $session = PlaySession::first();
        $todayStr = Carbon::now('Asia/Jayapura')->format('Y-m-d');

        $booking = Booking::create([
            'code' => 'PRIV0001',
            'user_id' => $user->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);

        BookingSlot::create([
            'booking_id' => $booking->id,
            'court_id' => $court->id,
            'play_session_id' => $session->id,
            'play_date' => $todayStr,
        ]);

        $response = $this->getJson("/api/jadwal/hari?tanggal={$todayStr}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'date',
                'is_today',
                'courts',
                'sessions',
                'grid',
                'summary',
            ],
        ]);

        $content = $response->getContent();

        // DILARANG memuat data pribadi: nomor HP, email, atau password
        $this->assertStringNotContainsString('081234567899', $content);
        $this->assertStringNotContainsString('privat@test.com', $content);
        $this->assertStringNotContainsString('total_price', $content);

        // NAMA PB boleh tampil
        $this->assertStringContainsString('PB Rahasia', $content);
    }

    /**
     * Uji API JSON /api/jadwal/bulan mengembalikan status success dan struktur hari.
     */
    public function test_api_month_endpoint_returns_json(): void
    {
        $currentMonth = Carbon::now('Asia/Jayapura')->format('Y-m');
        $response = $this->getJson("/api/jadwal/bulan?bulan={$currentMonth}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'year',
                'month',
                'month_name',
                'title',
                'start_padding',
                'days',
            ],
        ]);
    }
}
