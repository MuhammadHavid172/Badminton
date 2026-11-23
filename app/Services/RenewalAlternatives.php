<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\SlotBlock;
use Carbon\Carbon;

class RenewalAlternatives
{
    /**
     * Mengecek ketersediaan 4 slot perpanjangan untuk lapangan & sesi yang sama,
     * serta mencarikan opsi alternatif jika terjadi bentrok.
     */
    public function findAlternatives(Booking $originalBooking, ?Carbon $now = null): array
    {
        $nowJayapura = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');

        // Cari slot terakhir dari booking yang akan diperpanjang
        $lastSlot = $originalBooking->slots()->orderBy('play_date', 'desc')->firstOrFail();
        $originalCourt = $lastSlot->court;
        $originalSession = $lastSlot->session;

        // 4 tanggal lanjutan mingguan (+7, +14, +21, +28 hari dari tanggal terakhir)
        $lastDate = Carbon::parse($lastSlot->play_date, 'Asia/Jayapura');
        $targetDates = [];
        for ($i = 1; $i <= 4; $i++) {
            $tDate = $lastDate->copy()->addWeeks($i);
            $targetDates[] = [
                'index' => $i,
                'date' => $tDate->format('Y-m-d'),
                'formatted' => tanggal_indonesia($tDate, true, false),
            ];
        }

        // 1. Cek ketersediaan untuk lapangan & sesi semula
        $conflicts = $this->checkConflictsForCourtSession($originalCourt, $originalSession, $targetDates, $nowJayapura);

        if (empty($conflicts)) {
            return [
                'available' => true,
                'target_dates' => $targetDates,
                'court' => $originalCourt,
                'session' => $originalSession,
                'conflicts' => [],
                'alternative_courts' => [],
                'alternative_sessions' => [],
                'has_alternatives' => false,
            ];
        }

        // 2. Jika bentrok, cari Alternatif (a): Lapangan lain di sesi dan hari yang sama
        $allCourts = Court::where('is_active', true)->where('id', '!=', $originalCourt->id)->orderBy('sort_order')->get();
        $alternativeCourts = [];

        foreach ($allCourts as $court) {
            $courtConflicts = $this->checkConflictsForCourtSession($court, $originalSession, $targetDates, $nowJayapura);
            if (empty($courtConflicts)) {
                $alternativeCourts[] = [
                    'court' => $court,
                    'session' => $originalSession,
                    'label' => "{$court->name} (Sesi yang sama: {$originalSession->label})",
                ];
            }
        }

        // 3. Cari Alternatif (b): Sesi lain di lapangan dan hari yang sama
        $allSessions = PlaySession::where('is_active', true)->where('id', '!=', $originalSession->id)->orderBy('sort_order')->get();
        $alternativeSessions = [];

        foreach ($allSessions as $session) {
            $sessionConflicts = $this->checkConflictsForCourtSession($originalCourt, $session, $targetDates, $nowJayapura);
            if (empty($sessionConflicts)) {
                $alternativeSessions[] = [
                    'court' => $originalCourt,
                    'session' => $session,
                    'label' => "{$session->label} ({$session->formattedRange()} WIT di {$originalCourt->name})",
                ];
            }
        }

        return [
            'available' => false,
            'target_dates' => $targetDates,
            'court' => $originalCourt,
            'session' => $originalSession,
            'conflicts' => $conflicts,
            'alternative_courts' => $alternativeCourts,
            'alternative_sessions' => $alternativeSessions,
            'has_alternatives' => (!empty($alternativeCourts) || !empty($alternativeSessions)),
        ];
    }

    /**
     * Memeriksa bentrok pada keempat tanggal untuk kombinasi lapangan & sesi tertentu.
     */
    protected function checkConflictsForCourtSession(Court $court, PlaySession $session, array $targetDates, Carbon $nowJayapura): array
    {
        $conflicts = [];

        foreach ($targetDates as $item) {
            $dateStr = $item['date'];

            // Cek apakah slot sudah lewat
            $sessionEnd = Carbon::parse("{$dateStr} {$session->end_time}", 'Asia/Jayapura');
            if ($nowJayapura->greaterThanOrEqualTo($sessionEnd)) {
                $conflicts[$dateStr] = "Sesi telah lewat pada {$item['formatted']}";
                continue;
            }

            // Cek blokir slot
            $isBlocked = SlotBlock::where('court_id', $court->id)
                ->where('play_date', $dateStr)
                ->where(function ($q) use ($session) {
                    $q->whereNull('play_session_id')
                      ->orWhere('play_session_id', $session->id);
                })
                ->exists();

            if ($isBlocked) {
                $conflicts[$dateStr] = "Ditutup untuk pemeliharaan pada {$item['formatted']}";
                continue;
            }

            // Cek booking aktif
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

        return $conflicts;
    }
}
