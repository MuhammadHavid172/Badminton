<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji skema tabel users dan peran (UserRole).
     */
    public function test_users_table_and_roles(): void
    {
        $user = User::create([
            'name' => 'Tester',
            'role' => UserRole::CUSTOMER,
            'phone' => '081234567890',
            'email' => 'tester@test.com',
            'team_name' => 'PB Tester',
            'password' => Hash::make('secret123'),
        ]);

        $this->assertDatabaseHas('users', ['phone' => '081234567890']);
        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertTrue($user->isCustomer());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isOwner());

        $admin = User::create([
            'name' => 'Admin Test',
            'role' => UserRole::ADMIN,
            'phone' => '081234567891',
            'password' => Hash::make('secret123'),
        ]);
        $this->assertTrue($admin->isAdmin());
    }

    /**
     * Uji relasi courts dan play_sessions dengan slots dan blocks.
     */
    public function test_court_and_session_relations(): void
    {
        $court = Court::create(['name' => 'Lapangan A', 'sort_order' => 1]);
        $session = PlaySession::create([
            'label' => 'Sesi 1',
            'start_time' => '08:00',
            'end_time' => '11:00',
            'sort_order' => 1,
        ]);

        $this->assertEquals('08.00-11.00', $session->formattedRange());

        $user = User::create([
            'name' => 'Admin Test',
            'role' => UserRole::ADMIN,
            'phone' => '081234567892',
            'password' => Hash::make('secret123'),
        ]);

        $block = SlotBlock::create([
            'court_id' => $court->id,
            'play_date' => '2026-11-23',
            'play_session_id' => $session->id,
            'reason' => 'Perawatan',
            'created_by' => $user->id,
        ]);

        $this->assertTrue($court->slotBlocks->contains($block));
        $this->assertTrue($session->slotBlocks->contains($block));
    }

    /**
     * Uji constraint UNIQUE (court_id, play_session_id, play_date) pada booking_slots.
     * Ini mencegah booking ganda (double-booking).
     */
    public function test_booking_slot_unique_constraint(): void
    {
        $court = Court::create(['name' => 'Lapangan 1', 'sort_order' => 1]);
        $session = PlaySession::create([
            'label' => 'Sesi 1',
            'start_time' => '08:00',
            'end_time' => '11:00',
            'sort_order' => 1,
        ]);
        $user = User::create([
            'name' => 'User 1',
            'phone' => '081234567893',
            'password' => Hash::make('secret'),
        ]);

        $booking1 = Booking::create([
            'code' => 'BOOK0001',
            'user_id' => $user->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
        ]);

        BookingSlot::create([
            'booking_id' => $booking1->id,
            'court_id' => $court->id,
            'play_session_id' => $session->id,
            'play_date' => '2026-11-23',
        ]);

        $booking2 = Booking::create([
            'code' => 'BOOK0002',
            'user_id' => $user->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
        ]);

        // Slot kedua pada lapangan, sesi, dan tanggal sama WAJIB gagal karena constraint UNIQUE
        $this->expectException(QueryException::class);

        BookingSlot::create([
            'booking_id' => $booking2->id,
            'court_id' => $court->id,
            'play_session_id' => $session->id,
            'play_date' => '2026-11-23',
        ]);
    }

    /**
     * Uji helper Setting::get dan Setting::set dengan caching.
     */
    public function test_setting_get_and_set(): void
    {
        Setting::set('test_key', 'test_value');
        $this->assertEquals('test_value', Setting::get('test_key'));

        Setting::set('test_int', 42);
        $this->assertEquals(42, Setting::getInt('test_int'));

        $this->assertEquals('default_val', Setting::get('non_existent', 'default_val'));
    }

    /**
     * Uji pencatatan aktivitas ActivityLogger.
     */
    public function test_activity_logger(): void
    {
        $user = User::create([
            'name' => 'Admin Logger',
            'role' => UserRole::ADMIN,
            'phone' => '081234567894',
            'password' => Hash::make('secret'),
        ]);

        $log = ActivityLogger::log(
            'slot.blocked',
            'Admin memblokir Lapangan 1',
            null,
            ['court_id' => 1],
            $user
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'action' => 'slot.blocked',
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
    }
}
