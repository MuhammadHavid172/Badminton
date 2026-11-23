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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji DatabaseSeeder berjalan sukses dan mengikuti data di config/gor.php.
     */
    public function test_seeders_run_and_follow_config(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Lapangan dari config
        $expectedCourts = config('gor.courts');
        $this->assertCount(count($expectedCourts), Court::all());
        foreach ($expectedCourts as $court) {
            $this->assertDatabaseHas('courts', ['name' => $court['name']]);
        }

        // 2. Sesi dari config
        $expectedSessions = config('gor.sessions');
        $this->assertCount(count($expectedSessions), PlaySession::all());
        foreach ($expectedSessions as $session) {
            $this->assertDatabaseHas('play_sessions', ['label' => $session['label']]);
        }

        // 3. Settings default
        $this->assertEquals(200000, Setting::getInt('price_single'));
        $this->assertEquals(600000, Setting::getInt('price_package'));
        $this->assertEquals(15, Setting::getInt('hold_minutes'));
        $this->assertEquals(2, Setting::getInt('max_pending_bookings'));
        $this->assertEquals(1, Setting::getInt('renew_notice_remaining'));
        $this->assertEquals(6, Setting::getInt('last_day_notice_hour'));

        // 4. Akun demo
        $admin = User::where('phone', '081100000001')->first();
        $this->assertNotNull($admin);
        $this->assertEquals(UserRole::ADMIN, $admin->role);

        $customer = User::where('phone', '081100000003')->first();
        $this->assertNotNull($customer);
        $this->assertEquals(UserRole::CUSTOMER, $customer->role);
        $this->assertEquals('PB Rajawali', $customer->team_name);

        $owner = User::where('phone', '081100000004')->first();
        $this->assertNotNull($owner);
        $this->assertEquals(UserRole::OWNER, $owner->role);

        // 5. Demo bookings
        // Harian paid
        $harianPaid = Booking::where('type', BookingType::SINGLE)
            ->where('status', BookingStatus::PAID)
            ->first();
        $this->assertNotNull($harianPaid);
        $this->assertGreaterThanOrEqual(1, $harianPaid->slots()->count());

        // Bulanan dengan 4 slot (sisa 1)
        $bulanan = Booking::where('type', BookingType::PACKAGE)
            ->where('status', BookingStatus::PAID)
            ->first();
        $this->assertNotNull($bulanan);
        $this->assertCount(4, $bulanan->slots);

        // Pending payment
        $pending = Booking::where('status', BookingStatus::PENDING_PAYMENT)->first();
        $this->assertNotNull($pending);
        $this->assertTrue($pending->expires_at->isFuture());

        // Expired booking (slot dilepas)
        $expired = Booking::where('status', BookingStatus::EXPIRED)->first();
        $this->assertNotNull($expired);
        $this->assertCount(0, $expired->slots);

        // Slot block
        $this->assertGreaterThanOrEqual(1, SlotBlock::count());
    }
}
