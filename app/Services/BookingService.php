<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Membersihkan penahanan slot (hold) yang telah kedaluwarsa.
     */
    public function cleanupExpiredHolds(): int
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');

        $expiredBookings = Booking::where('status', BookingStatus::PENDING_PAYMENT)
            ->where('expires_at', '<=', $nowJayapura)
            ->get();

        $count = 0;
        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                // Bebaskan slot dengan menghapus baris booking_slots
                $booking->slots()->delete();
                $booking->update(['status' => BookingStatus::EXPIRED]);

                Payment::where('booking_id', $booking->id)
                    ->where('status', PaymentStatus::PENDING)
                    ->update(['status' => PaymentStatus::FAILED]);
            });
            $count++;
        }

        return $count;
    }

    /**
     * Memeriksa ketersediaan slot untuk kategori Harian dan Bulanan.
     * Mengembalikan data tanggal, status ketersediaan, dan daftar tanggal bentrok (jika ada).
     */
    public function checkSlotAvailability(Court $court, PlaySession $session, Carbon $startDate, ?Carbon $now = null): array
    {
        $nowJayapura = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $this->cleanupExpiredHolds();

        $startDateStr = $startDate->format('Y-m-d');
        $sessionEnd = Carbon::parse("{$startDateStr} {$session->end_time}", 'Asia/Jayapura');

        // 1. Cek apakah sesi pada tanggal awal sudah lewat
        $isPast = $nowJayapura->greaterThanOrEqualTo($sessionEnd);

        // 2. Siapkan 4 tanggal untuk paket Bulanan (+0, +7, +14, +21 hari)
        $packageDates = [];
        for ($i = 0; $i < 4; $i++) {
            $pDate = $startDate->copy()->addWeeks($i);
            $packageDates[] = [
                'index' => $i + 1,
                'date' => $pDate->format('Y-m-d'),
                'formatted' => tanggal_indonesia($pDate, true, false),
            ];
        }

        // 3. Cek bentrok untuk setiap tanggal
        $conflicts = [];
        foreach ($packageDates as $item) {
            $dateStr = $item['date'];

            // Cek blokir penutupan lapangan
            $isBlocked = SlotBlock::where('court_id', $court->id)
                ->where('play_date', $dateStr)
                ->where(function ($q) use ($session) {
                    $q->whereNull('play_session_id')
                      ->orWhere('play_session_id', $session->id);
                })
                ->exists();

            if ($isBlocked) {
                $conflicts[$dateStr] = "Ditutup untuk pemeliharaan/acara pada {$item['formatted']}";
                continue;
            }

            // Cek booking slot aktif
            $existingSlot = BookingSlot::with('booking')
                ->where('court_id', $court->id)
                ->where('play_session_id', $session->id)
                ->where('play_date', $dateStr)
                ->first();

            if ($existingSlot && $existingSlot->booking) {
                $b = $existingSlot->booking;
                if ($b->status === BookingStatus::PAID) {
                    $conflicts[$dateStr] = "Sudah terisi pada {$item['formatted']}";
                } elseif ($b->status === BookingStatus::PENDING_PAYMENT && $b->expires_at && $b->expires_at->gt($nowJayapura)) {
                    $conflicts[$dateStr] = "Sedang ditahan pembayaran pada {$item['formatted']}";
                }
            }
        }

        $singleConflict = $conflicts[$startDateStr] ?? null;
        $isSingleAvailable = !$isPast && is_null($singleConflict);
        $isPackageAvailable = !$isPast && empty($conflicts);

        $priceSingle = Setting::getInt('price_single', 200000);
        $pricePackage = Setting::getInt('price_package', 600000);
        $saving = (4 * $priceSingle) - $pricePackage;

        return [
            'court' => $court,
            'session' => $session,
            'start_date' => $startDateStr,
            'start_date_formatted' => tanggal_indonesia($startDate, true, false),
            'is_past' => $isPast,
            'single' => [
                'available' => $isSingleAvailable,
                'conflict' => $singleConflict,
                'price' => $priceSingle,
            ],
            'package' => [
                'available' => $isPackageAvailable,
                'dates' => $packageDates,
                'conflicts' => $conflicts,
                'price' => $pricePackage,
                'saving' => $saving > 0 ? $saving : 0,
            ],
        ];
    }

    /**
     * Membuat booking baru dalam transaksi database dengan pengamanan konkurensi.
     *
     * @throws ValidationException
     */
    public function createBooking(
        User $user,
        Court $court,
        PlaySession $session,
        string $startDateStr,
        BookingType $type,
        ?string $notes = null,
        ?Carbon $now = null
    ): Booking {
        $nowJayapura = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');

        // Batas maksimal pending booking per pengguna
        $maxPending = Setting::getInt('max_pending_bookings', 2);
        $activePendingCount = Booking::where('user_id', $user->id)
            ->where('status', BookingStatus::PENDING_PAYMENT)
            ->where('expires_at', '>', $nowJayapura)
            ->count();

        if ($activePendingCount >= $maxPending) {
            throw ValidationException::withMessages([
                'booking' => "Kamu memiliki {$activePendingCount} booking yang sedang menunggu pembayaran. Selesaikan pembayaran atau batalkan terlebih dahulu sebelum membuat booking baru.",
            ]);
        }

        $startDate = Carbon::parse($startDateStr, 'Asia/Jayapura')->startOfDay();
        $availability = $this->checkSlotAvailability($court, $session, $startDate, $nowJayapura);

        if ($availability['is_past']) {
            throw ValidationException::withMessages([
                'booking' => 'Sesi waktu bermain untuk slot ini sudah lewat. Silakan pilih slot lain ya.',
            ]);
        }

        if ($type === BookingType::SINGLE) {
            if (!$availability['single']['available']) {
                $reason = $availability['single']['conflict'] ?? 'Slot tidak tersedia.';
                throw ValidationException::withMessages([
                    'booking' => "Slot Harian tidak dapat dipesan: {$reason}",
                ]);
            }
            $targetDates = [$startDateStr];
            $totalPrice = Setting::getInt('price_single', 200000);
        } else {
            if (!$availability['package']['available']) {
                $conflictList = implode(', ', $availability['package']['conflicts']);
                throw ValidationException::withMessages([
                    'booking' => "Sewa Bulanan tidak dapat dipesan karena bentrok pada tanggal: {$conflictList}. Silakan pilih sesi atau hari lain ya.",
                ]);
            }
            $targetDates = array_column($availability['package']['dates'], 'date');
            $totalPrice = Setting::getInt('price_package', 600000);
        }

        $holdMinutes = Setting::getInt('hold_minutes', 15);
        $expiresAt = $nowJayapura->copy()->addMinutes($holdMinutes);

        try {
            return DB::transaction(function () use ($user, $court, $session, $type, $totalPrice, $expiresAt, $notes, $targetDates) {
                // Buat Booking
                $booking = Booking::create([
                    'code' => Booking::generateCode(),
                    'user_id' => $user->id,
                    'type' => $type,
                    'status' => BookingStatus::PENDING_PAYMENT,
                    'total_price' => $totalPrice,
                    'expires_at' => $expiresAt,
                    'notes' => $notes,
                    'created_by' => $user->id,
                ]);

                // Buat Booking Slot (Constraint UNIQUE pada database adalah penjaga konkurensi utama)
                foreach ($targetDates as $dateStr) {
                    BookingSlot::create([
                        'booking_id' => $booking->id,
                        'court_id' => $court->id,
                        'play_session_id' => $session->id,
                        'play_date' => $dateStr,
                    ]);
                }

                // Buat Payment Record Awal
                Payment::create([
                    'booking_id' => $booking->id,
                    'method' => 'simulated',
                    'provider' => config('gor.payment_driver', 'simulated'),
                    'amount' => $totalPrice,
                    'status' => PaymentStatus::PENDING,
                    'reference' => 'SIM-' . $booking->code,
                ]);

                ActivityLogger::log(
                    'booking.created',
                    "Booking {$booking->type->label()} {$booking->code} dibuat (menunggu pembayaran)",
                    $booking,
                    ['slots_count' => count($targetDates), 'total_price' => $totalPrice],
                    $user
                );

                return $booking;
            });
        } catch (QueryException $e) {
            // Tangkap pelanggaran unique constraint jika ada pengguna lain yang memesan bersamaan
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'unique_court_session_date')) {
                throw ValidationException::withMessages([
                    'booking' => 'Slot ini baru saja diambil orang lain. Pilih slot lain ya.',
                ]);
            }

            throw $e;
        }
    }

    /**
     * Membatalkan booking pending payment oleh pemilik.
     */
    public function cancelPendingBooking(Booking $booking, User $actor, ?string $reason = 'Dibatalkan oleh pemesan'): void
    {
        if ($booking->status !== BookingStatus::PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'booking' => 'Hanya booking yang belum dibayar yang dapat dibatalkan langsung.',
            ]);
        }

        DB::transaction(function () use ($booking, $actor, $reason) {
            // Bebaskan slot dengan menghapus barisnya
            $booking->slots()->delete();

            $booking->update([
                'status' => BookingStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancel_reason' => $reason,
            ]);

            Payment::where('booking_id', $booking->id)
                ->where('status', PaymentStatus::PENDING)
                ->update(['status' => PaymentStatus::FAILED]);

            ActivityLogger::log(
                'booking.cancelled',
                "Booking {$booking->code} dibatalkan oleh {$actor->name}",
                $booking,
                ['reason' => $reason],
                $actor
            );
        });
    }

    /**
     * Simulasi pembayaran lunas (khusus local environment).
     */
    public function simulatePaymentSuccess(Booking $booking, User $actor): void
    {
        if ($booking->status !== BookingStatus::PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'booking' => 'Booking ini tidak dalam status menunggu pembayaran.',
            ]);
        }

        DB::transaction(function () use ($booking, $actor) {
            $booking->update([
                'status' => BookingStatus::PAID,
            ]);

            Payment::where('booking_id', $booking->id)
                ->update([
                    'status' => PaymentStatus::PAID,
                    'paid_at' => now(),
                    'recorded_by' => $actor->id,
                ]);

            ActivityLogger::log(
                'booking.paid_simulated',
                "Pembayaran simulasi lunas untuk booking {$booking->code}",
                $booking,
                ['amount' => $booking->total_price],
                $actor
            );
        });
    }
}
