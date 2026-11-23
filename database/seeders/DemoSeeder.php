<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PlaySession;
use App\Models\SlotBlock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed data akun demo dan contoh transaksi jadwal untuk lingkungan lokal.
     */
    public function run(): void
    {
        // 1. Akun Pengguna Demo
        $admin = User::firstOrCreate(
            ['phone' => '081100000001'],
            [
                'name' => 'Admin GOR (Contoh)',
                'email' => 'admin@gor.test',
                'role' => UserRole::ADMIN,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $customer = User::firstOrCreate(
            ['phone' => '081100000003'],
            [
                'name' => 'Budi Santoso (Contoh)',
                'email' => 'budi@rajawali.test',
                'team_name' => 'PB Rajawali',
                'role' => UserRole::CUSTOMER,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $owner = User::firstOrCreate(
            ['phone' => '081100000004'],
            [
                'name' => 'Pemilik GOR (Contoh)',
                'email' => 'owner@gor.test',
                'role' => UserRole::OWNER,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $customer2 = User::firstOrCreate(
            ['phone' => '081100000005'],
            [
                'name' => 'Ahmad Kurnia (Contoh)',
                'email' => 'ahmad@garuda.test',
                'team_name' => 'PB Garuda',
                'role' => UserRole::CUSTOMER,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Ambil data lapangan dan sesi yang tersedia
        $courts = Court::orderBy('sort_order')->get();
        $sessions = PlaySession::orderBy('sort_order')->get();

        if ($courts->isEmpty() || $sessions->isEmpty()) {
            return;
        }

        // Tentukan Senin terdekat menurut zona waktu Asia/Jayapura
        $now = Carbon::now('Asia/Jayapura');
        $nearestMonday = $now->isMonday()
            ? $now->copy()->startOfDay()
            : $now->copy()->next(Carbon::MONDAY)->startOfDay();

        $court1 = $courts->first();
        $court2 = $courts->count() > 1 ? $courts->get(1) : $court1;
        $court3 = $courts->count() > 2 ? $courts->get(2) : $court2;

        $session1 = $sessions->first();
        $session2 = $sessions->count() > 1 ? $sessions->get(1) : $session1;
        $session3 = $sessions->count() > 2 ? $sessions->get(2) : $session2;
        $session4 = $sessions->count() > 3 ? $sessions->get(3) : $session3;

        // 2. Demo Booking Harian 1: Dibayar (Court 1, Session 1, Senin)
        $bookingHarian1 = Booking::create([
            'code' => Booking::generateCode(),
            'user_id' => $customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'notes' => 'Contoh booking Harian dibayar',
            'created_by' => $customer->id,
        ]);
        BookingSlot::create([
            'booking_id' => $bookingHarian1->id,
            'court_id' => $court1->id,
            'play_session_id' => $session1->id,
            'play_date' => $nearestMonday->toDateString(),
        ]);
        Payment::create([
            'booking_id' => $bookingHarian1->id,
            'method' => 'transfer',
            'provider' => 'simulated',
            'amount' => 200000,
            'status' => PaymentStatus::PAID,
            'reference' => 'SIM-' . $bookingHarian1->code,
            'paid_at' => now(),
        ]);

        // 3. Demo Booking Harian 2: Dibayar (Court 2, Session 2, Senin)
        $bookingHarian2 = Booking::create([
            'code' => Booking::generateCode(),
            'user_id' => $customer2->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PAID,
            'total_price' => 200000,
            'notes' => 'Contoh booking Harian dibayar 2',
            'created_by' => $customer2->id,
        ]);
        BookingSlot::create([
            'booking_id' => $bookingHarian2->id,
            'court_id' => $court2->id,
            'play_session_id' => $session2->id,
            'play_date' => $nearestMonday->toDateString(),
        ]);
        Payment::create([
            'booking_id' => $bookingHarian2->id,
            'method' => 'transfer',
            'provider' => 'simulated',
            'amount' => 200000,
            'status' => PaymentStatus::PAID,
            'reference' => 'SIM-' . $bookingHarian2->code,
            'paid_at' => now(),
        ]);

        // 4. Demo Booking Bulanan yang tersisa 1 pertemuan:
        // 4 pertemuan: 3 pertemuan lampau (-21, -14, -7 hari), dan pertemuan ke-4 pada Senin terdekat ini
        $bookingBulanan = Booking::create([
            'code' => Booking::generateCode(),
            'user_id' => $customer->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
            'notes' => 'Contoh booking Bulanan sisa 1 pertemuan',
            'created_by' => $customer->id,
        ]);
        Payment::create([
            'booking_id' => $bookingBulanan->id,
            'method' => 'transfer',
            'provider' => 'simulated',
            'amount' => 600000,
            'status' => PaymentStatus::PAID,
            'reference' => 'SIM-' . $bookingBulanan->code,
            'paid_at' => now()->subDays(25),
        ]);

        $dates = [
            $nearestMonday->copy()->subWeeks(3)->toDateString(),
            $nearestMonday->copy()->subWeeks(2)->toDateString(),
            $nearestMonday->copy()->subWeek()->toDateString(),
            $nearestMonday->toDateString(), // Pertemuan terakhir (sisa 1x)
        ];

        foreach ($dates as $index => $slotDate) {
            BookingSlot::create([
                'booking_id' => $bookingBulanan->id,
                'court_id' => $court1->id,
                'play_session_id' => $session4->id,
                'play_date' => $slotDate,
                'checked_in_at' => $index < 3 ? Carbon::parse($slotDate . ' 19:10:00') : null,
                'checked_in_by' => $index < 3 ? $admin->id : null,
            ]);
        }

        // 5. Demo Booking Pending Payment (masih berlaku 15 menit ke depan)
        $bookingPending = Booking::create([
            'code' => Booking::generateCode(),
            'user_id' => $customer2->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::PENDING_PAYMENT,
            'total_price' => 200000,
            'expires_at' => now()->addMinutes(12),
            'notes' => 'Contoh booking menunggu pembayaran aktif',
            'created_by' => $customer2->id,
        ]);
        BookingSlot::create([
            'booking_id' => $bookingPending->id,
            'court_id' => $court2->id,
            'play_session_id' => $session3->id,
            'play_date' => $nearestMonday->toDateString(),
        ]);
        Payment::create([
            'booking_id' => $bookingPending->id,
            'method' => 'transfer',
            'provider' => 'simulated',
            'amount' => 200000,
            'status' => PaymentStatus::PENDING,
            'reference' => 'SIM-' . $bookingPending->code,
        ]);

        // 6. Demo Booking Kedaluwarsa (slot sudah dibebaskan / dihapus)
        $bookingExpired = Booking::create([
            'code' => Booking::generateCode(),
            'user_id' => $customer->id,
            'type' => BookingType::SINGLE,
            'status' => BookingStatus::EXPIRED,
            'total_price' => 200000,
            'expires_at' => now()->subHours(2),
            'notes' => 'Contoh booking kedaluwarsa (slot telah dibebaskan)',
            'created_by' => $customer->id,
        ]);
        Payment::create([
            'booking_id' => $bookingExpired->id,
            'method' => 'transfer',
            'provider' => 'simulated',
            'amount' => 200000,
            'status' => PaymentStatus::FAILED,
            'reference' => 'SIM-' . $bookingExpired->code,
        ]);

        // 7. Demo Penutupan Lapangan (slot_blocks)
        SlotBlock::create([
            'court_id' => $court3->id,
            'play_date' => $nearestMonday->toDateString(),
            'play_session_id' => $session1->id,
            'reason' => 'Perawatan rutin karpet & lampu (Contoh)',
            'created_by' => $admin->id,
        ]);

        // 8. Demo Booking Bulanan: Terakhir Hari Ini (Pertemuan ke-4 berlangsung hari ini)
        // Pastikan tidak tabrakan dengan slot yang ada
        $todayStr = $now->toDateString();
        $isSlotAvailable = !BookingSlot::where('court_id', $court2->id)
            ->where('play_session_id', $session4->id)
            ->where('play_date', $todayStr)
            ->exists();

        if ($isSlotAvailable) {
            $bookingTerakhirHariIni = Booking::create([
                'code' => Booking::generateCode(),
                'user_id' => $customer->id,
                'type' => BookingType::PACKAGE,
                'status' => BookingStatus::PAID,
                'total_price' => 600000,
                'notes' => 'Contoh sewa Bulanan: Terakhir Hari Ini',
                'created_by' => $customer->id,
            ]);
            Payment::create([
                'booking_id' => $bookingTerakhirHariIni->id,
                'method' => 'transfer',
                'provider' => 'simulated',
                'amount' => 600000,
                'status' => PaymentStatus::PAID,
                'reference' => 'SIM-' . $bookingTerakhirHariIni->code,
                'paid_at' => now()->subDays(21),
            ]);

            $datesTerakhir = [
                $now->copy()->subWeeks(3)->toDateString(),
                $now->copy()->subWeeks(2)->toDateString(),
                $now->copy()->subWeek()->toDateString(),
                $todayStr, // Hari ini (pertemuan terakhir)
            ];

            foreach ($datesTerakhir as $index => $slotDate) {
                // Hindari duplikasi jika slot tanggal lampau sudah dipakai
                $slotExists = BookingSlot::where('court_id', $court2->id)
                    ->where('play_session_id', $session4->id)
                    ->where('play_date', $slotDate)
                    ->exists();

                if (!$slotExists) {
                    BookingSlot::create([
                        'booking_id' => $bookingTerakhirHariIni->id,
                        'court_id' => $court2->id,
                        'play_session_id' => $session4->id,
                        'play_date' => $slotDate,
                        'checked_in_at' => $index < 3 ? Carbon::parse($slotDate . ' 19:05:00') : null,
                        'checked_in_by' => $index < 3 ? $admin->id : null,
                    ]);
                }
            }
        }

        // 9. Demo Booking Bulanan: Baru Berakhir (Seluruh 4 sesi lampau, selesai kemarin/2 hari lalu)
        $bookingBaruBerakhir = Booking::create([
            'code' => Booking::generateCode(),
            'user_id' => $customer2->id,
            'type' => BookingType::PACKAGE,
            'status' => BookingStatus::PAID,
            'total_price' => 600000,
            'notes' => 'Contoh sewa Bulanan: Baru Berakhir',
            'created_by' => $customer2->id,
        ]);
        Payment::create([
            'booking_id' => $bookingBaruBerakhir->id,
            'method' => 'transfer',
            'provider' => 'simulated',
            'amount' => 600000,
            'status' => PaymentStatus::PAID,
            'reference' => 'SIM-' . $bookingBaruBerakhir->code,
            'paid_at' => now()->subDays(30),
        ]);

        $datesBerakhir = [
            $now->copy()->subDays(23)->toDateString(),
            $now->copy()->subDays(16)->toDateString(),
            $now->copy()->subDays(9)->toDateString(),
            $now->copy()->subDays(2)->toDateString(), // 2 hari lalu (selesai)
        ];

        foreach ($datesBerakhir as $index => $slotDate) {
            $slotExists = BookingSlot::where('court_id', $court3->id)
                ->where('play_session_id', $session2->id)
                ->where('play_date', $slotDate)
                ->exists();

            if (!$slotExists) {
                BookingSlot::create([
                    'booking_id' => $bookingBaruBerakhir->id,
                    'court_id' => $court3->id,
                    'play_session_id' => $session2->id,
                    'play_date' => $slotDate,
                    'checked_in_at' => Carbon::parse($slotDate . ' 11:15:00'),
                    'checked_in_by' => $admin->id,
                ]);
            }
        }
    }
}
