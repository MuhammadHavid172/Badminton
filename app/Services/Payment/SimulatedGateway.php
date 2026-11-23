<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use App\Services\ActivityLogger;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimulatedGateway implements PaymentGateway
{
    /**
     * Memproses dan mengonfirmasi pembayaran simulasi berhasil secara aman & idempoten.
     *
     * @throws ValidationException
     */
    public function markAsPaid(Booking $booking, User $actor, array $payload = []): Payment
    {
        return DB::transaction(function () use ($booking, $actor, $payload) {
            // Kunci baris booking untuk konkurensi aman & idempoten
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            // 1. Idempoten: Jika sudah berstatus PAID, kembalikan pembayaran yang sudah ada
            if ($lockedBooking->status === BookingStatus::PAID) {
                return Payment::where('booking_id', $lockedBooking->id)
                    ->where('status', PaymentStatus::PAID)
                    ->first() ?? Payment::where('booking_id', $lockedBooking->id)->latest()->first();
            }

            // 2. Cegah pembayaran pada booking yang expired atau cancelled
            if ($lockedBooking->status === BookingStatus::EXPIRED || $lockedBooking->status === BookingStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'payment' => 'Booking ini telah dibatalkan atau kedaluwarsa dan tidak dapat dibayar lagi.',
                ]);
            }

            // 3. Cek apakah batas waktu hold pembayaran telah lewat di zona Asia/Jayapura
            $nowJayapura = Carbon::now('Asia/Jayapura');
            if ($lockedBooking->expires_at && $lockedBooking->expires_at->lessThanOrEqualTo($nowJayapura)) {
                app(BookingService::class)->cleanupExpiredHolds();
                throw ValidationException::withMessages([
                    'payment' => 'Waktu penahanan pembayaran telah habis. Slot telah dibebaskan kembali.',
                ]);
            }

            // 4. Ubah status booking menjadi PAID
            $lockedBooking->update([
                'status' => BookingStatus::PAID,
            ]);

            // 5. Update atau buat record Payment
            $payment = Payment::where('booking_id', $lockedBooking->id)
                ->where('status', PaymentStatus::PENDING)
                ->first();

            if ($payment) {
                $payment->update([
                    'status' => PaymentStatus::PAID,
                    'paid_at' => $nowJayapura,
                    'recorded_by' => $actor->id,
                    'payload' => array_merge($payment->payload ?? [], $payload, [
                        'ip' => request()->ip(),
                        'simulated' => true,
                    ]),
                ]);
            } else {
                $payment = Payment::create([
                    'booking_id' => $lockedBooking->id,
                    'method' => 'simulated',
                    'provider' => config('gor.payment_driver', 'simulated'),
                    'amount' => $lockedBooking->total_price,
                    'status' => PaymentStatus::PAID,
                    'reference' => 'SIM-' . $lockedBooking->code . '-' . time(),
                    'payload' => array_merge($payload, [
                        'ip' => request()->ip(),
                        'simulated' => true,
                    ]),
                    'recorded_by' => $actor->id,
                    'paid_at' => $nowJayapura,
                ]);
            }

            ActivityLogger::log(
                'payment.success',
                "Pembayaran simulasi untuk booking {$lockedBooking->code} berhasil",
                $lockedBooking,
                ['amount' => $lockedBooking->total_price, 'method' => 'simulated'],
                $actor
            );

            // 6. Kirim notifikasi database ke pengguna
            if ($lockedBooking->user) {
                $lockedBooking->user->notify(new PaymentSuccessNotification($lockedBooking));
            }

            return $payment;
        });
    }

    /**
     * Mencatat percobaan pembayaran yang gagal.
     */
    public function markAsFailed(Booking $booking, User $actor, string $reason = 'Simulasi pembayaran gagal'): Payment
    {
        return DB::transaction(function () use ($booking, $actor, $reason) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'method' => 'simulated',
                'provider' => config('gor.payment_driver', 'simulated'),
                'amount' => $booking->total_price,
                'status' => PaymentStatus::FAILED,
                'reference' => 'SIM-FAIL-' . $booking->code . '-' . time(),
                'payload' => [
                    'reason' => $reason,
                    'simulated' => true,
                    'ip' => request()->ip(),
                ],
                'recorded_by' => $actor->id,
            ]);

            ActivityLogger::log(
                'payment.failed',
                "Pembayaran simulasi untuk booking {$booking->code} gagal: {$reason}",
                $booking,
                ['reason' => $reason],
                $actor
            );

            return $payment;
        });
    }
}
