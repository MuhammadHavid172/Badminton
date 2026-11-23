<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\SlotBlock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleService
{
    /**
     * Menghasilkan grid jadwal harian per sesi dan lapangan.
     * Query efisien bebas N+1.
     *
     * @param Carbon|string $date
     * @param Carbon|null $now
     * @return array
     */
    public function dayGrid(Carbon|string $date, ?Carbon $now = null): array
    {
        $currentJayapura = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $targetDate = $date instanceof Carbon ? $date->copy()->setTimezone('Asia/Jayapura') : Carbon::parse($date, 'Asia/Jayapura');
        $dateStr = $targetDate->format('Y-m-d');
        $todayStr = $currentJayapura->format('Y-m-d');
        $isToday = ($dateStr === $todayStr);

        $courts = Court::where('is_active', true)->orderBy('sort_order')->get();
        $sessions = PlaySession::where('is_active', true)->orderBy('sort_order')->get();

        // 1. Muat seluruh blokir lapangan pada tanggal ini
        $slotBlocks = SlotBlock::where('play_date', $dateStr)->get();

        // 2. Muat seluruh booking_slots pada tanggal ini dengan eager-loading relasi booking & user
        $bookingSlots = BookingSlot::with(['booking.user', 'booking.slots.playSession'])
            ->where('play_date', $dateStr)
            ->get();

        // 3. Kumpulkan booking bulanan untuk pre-fetch renewals (cegah N+1)
        $packageBookingIds = $bookingSlots
            ->filter(fn(BookingSlot $slot) => $slot->booking && $slot->booking->type === BookingType::PACKAGE)
            ->pluck('booking.id')
            ->unique();

        $renewalsByBookingId = collect();
        if ($packageBookingIds->isNotEmpty()) {
            $renewalsByBookingId = Booking::whereIn('renewed_from_id', $packageBookingIds)
                ->get()
                ->groupBy('renewed_from_id');
        }

        // 4. Susun indeks untuk pencarian cepat
        $blocksKeyed = [];
        foreach ($slotBlocks as $block) {
            $sessionKey = $block->play_session_id ?? 'all';
            $blocksKeyed["{$block->court_id}_{$sessionKey}"] = $block;
        }

        $slotsKeyed = [];
        foreach ($bookingSlots as $bSlot) {
            $slotsKeyed["{$bSlot->court_id}_{$bSlot->play_session_id}"] = $bSlot;
        }

        $grid = [];
        $totalSlots = 0;
        $totalKosong = 0;
        $totalTerisi = 0;
        $totalMenungguBayar = 0;
        $totalDitutup = 0;
        $totalLewat = 0;

        foreach ($sessions as $session) {
            $startTime = Carbon::parse("{$dateStr} {$session->start_time}", 'Asia/Jayapura');
            $endTime = Carbon::parse("{$dateStr} {$session->end_time}", 'Asia/Jayapura');

            $isCurrentSession = $isToday && $currentJayapura->greaterThanOrEqualTo($startTime) && $currentJayapura->lessThan($endTime);
            $isPastSession = $currentJayapura->greaterThanOrEqualTo($endTime);

            $sessionRow = [
                'session' => [
                    'id' => $session->id,
                    'label' => $session->label,
                    'start_time' => substr($session->start_time, 0, 5),
                    'end_time' => substr($session->end_time, 0, 5),
                    'time_range' => $session->formattedRange(),
                    'is_current' => $isCurrentSession,
                    'is_past' => $isPastSession,
                ],
                'courts' => [],
            ];

            foreach ($courts as $court) {
                $totalSlots++;

                $blockSpecific = $blocksKeyed["{$court->id}_{$session->id}"] ?? null;
                $blockAll = $blocksKeyed["{$court->id}_all"] ?? null;
                $block = $blockSpecific ?? $blockAll;

                $bookingSlot = $slotsKeyed["{$court->id}_{$session->id}"] ?? null;

                // Hitung status slot sesuai urutan prioritas
                $status = SlotStatus::KOSONG;
                $label = null;
                $category = null;
                $isRemainingOne = false;
                $remainingMeetings = null;
                $reason = null;
                $isMine = false;
                $currentUserId = auth()->id();

                if ($block) {
                    // Prioritas 1: Ditutup
                    $status = SlotStatus::DITUTUP;
                    $reason = $block->reason;
                    $label = $block->reason;
                    $totalDitutup++;
                } elseif ($bookingSlot && $bookingSlot->booking) {
                    $booking = $bookingSlot->booking;

                    if ($currentUserId && $booking->user_id === $currentUserId) {
                        $isMine = true;
                    }

                    if ($booking->status === BookingStatus::PAID) {
                        // Prioritas 2: Terisi
                        $status = SlotStatus::TERISI;
                        $category = $booking->type->label(); // "Harian" atau "Bulanan"

                        // Label pemesan: nama tim jika ada, atau nama pelanggan
                        $user = $booking->user;
                        $label = $user ? ($user->team_name ?: $user->name) : 'Terisi';

                        if ($booking->type === BookingType::PACKAGE) {
                            $renewals = $renewalsByBookingId->get($booking->id, collect());
                            $lifecycle = new PackageLifecycle($booking, $renewals);
                            $isRemainingOne = $lifecycle->shouldShowRemainingOneBadge($currentJayapura);
                            $remainingMeetings = $lifecycle->remainingMeetings($currentJayapura);
                        }

                        $totalTerisi++;
                    } elseif ($booking->status === BookingStatus::PENDING_PAYMENT) {
                        // Prioritas 3: Menunggu Bayar (cek apakah belum kedaluwarsa)
                        $isHoldExpired = $booking->expires_at && $booking->expires_at->lessThanOrEqualTo($currentJayapura);

                        if (!$isHoldExpired) {
                            $status = SlotStatus::MENUNGGU_BAYAR;
                            $label = null; // Menunggu bayar TIDAK menampilkan nama pemesan
                            $totalMenungguBayar++;
                        } else {
                            // Hold kedaluwarsa dianggap kosong (atau lewat jika waktu sesi sudah lewat)
                            if ($isPastSession) {
                                $status = SlotStatus::LEWAT;
                                $label = 'Lewat';
                                $totalLewat++;
                            } else {
                                $status = SlotStatus::KOSONG;
                                $label = 'Kosong';
                                $totalKosong++;
                            }
                        }
                    } else {
                        // Booking berstatus cancelled atau expired
                        if ($isPastSession) {
                            $status = SlotStatus::LEWAT;
                            $label = 'Lewat';
                            $totalLewat++;
                        } else {
                            $status = SlotStatus::KOSONG;
                            $label = 'Kosong';
                            $totalKosong++;
                        }
                    }
                } else {
                    // Prioritas 4 & 5: Kosong atau Lewat
                    if ($isPastSession) {
                        $status = SlotStatus::LEWAT;
                        $label = 'Lewat';
                        $totalLewat++;
                    } else {
                        $status = SlotStatus::KOSONG;
                        $label = 'Kosong';
                        $totalKosong++;
                    }
                }

                $bookingUrl = null;
                if ($status === SlotStatus::KOSONG) {
                    $bookingUrl = route('booking.baru', [
                        'lapangan' => $court->id,
                        'sesi' => $session->id,
                        'tanggal' => $dateStr,
                    ]);
                }

                $sessionRow['courts'][] = [
                    'court_id' => $court->id,
                    'court_name' => $court->name,
                    'session_id' => $session->id,
                    'session_label' => $session->label,
                    'date' => $dateStr,
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'label' => $label,
                    'category' => $category,
                    'is_remaining_one' => $isRemainingOne,
                    'remaining_meetings' => $remainingMeetings,
                    'reason' => $reason,
                    'is_past' => $isPastSession,
                    'is_mine' => $isMine,
                    'booking_url' => $bookingUrl,
                ];
            }

            $grid[] = $sessionRow;
        }

        return [
            'date' => $dateStr,
            'date_formatted' => tanggal_indonesia($targetDate, true, false),
            'date_formatted_short' => tanggal_indonesia($targetDate, false, false),
            'is_today' => $isToday,
            'courts' => $courts->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray(),
            'sessions' => $sessions->map(fn($s) => [
                'id' => $s->id,
                'label' => $s->label,
                'time_range' => $s->formattedRange(),
            ])->toArray(),
            'grid' => $grid,
            'summary' => [
                'total_slots' => $totalSlots,
                'kosong' => $totalKosong,
                'terisi' => $totalTerisi,
                'menunggu_bayar' => $totalMenungguBayar,
                'ditutup' => $totalDitutup,
                'lewat' => $totalLewat,
            ],
        ];
    }

    /**
     * Menghasilkan ringkasan kalender bulanan.
     * Menghitung status slot per hari dengan query efisien bebas N+1.
     *
     * @param int $year
     * @param int $month
     * @param Carbon|null $now
     * @return array
     */
    public function monthSummary(int $year, int $month, ?Carbon $now = null): array
    {
        $currentJayapura = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $startOfMonth = Carbon::createFromDate($year, $month, 1, 'Asia/Jayapura')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $courts = Court::where('is_active', true)->orderBy('sort_order')->get();
        $sessions = PlaySession::where('is_active', true)->orderBy('sort_order')->get();

        $courtsCount = $courts->count();
        $sessionsCount = $sessions->count();
        $slotsPerDay = $courtsCount * $sessionsCount;

        // Muat seluruh blokir dalam rentang bulan
        $blocks = SlotBlock::whereBetween('play_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])->get();
        $blocksByDate = $blocks->groupBy(fn(SlotBlock $b) => $b->play_date instanceof Carbon ? $b->play_date->format('Y-m-d') : (string) $b->play_date);

        // Muat seluruh booking slot dalam rentang bulan
        $bookingSlots = BookingSlot::with('booking')
            ->whereBetween('play_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get();
        $slotsByDate = $bookingSlots->groupBy(fn(BookingSlot $s) => $s->play_date instanceof Carbon ? $s->play_date->format('Y-m-d') : (string) $s->play_date);

        $days = [];
        $cursor = $startOfMonth->copy();
        $todayStr = $currentJayapura->format('Y-m-d');

        while ($cursor->lte($endOfMonth)) {
            $dateStr = $cursor->format('Y-m-d');
            $isToday = ($dateStr === $todayStr);
            $isPastDate = $cursor->copy()->endOfDay()->lessThan($currentJayapura);

            $dateBlocks = $blocksByDate->get($dateStr, collect());
            $dateSlots = $slotsByDate->get($dateStr, collect());

            $blocksKeyed = [];
            foreach ($dateBlocks as $block) {
                $sessionKey = $block->play_session_id ?? 'all';
                $blocksKeyed["{$block->court_id}_{$sessionKey}"] = true;
            }

            $slotsKeyed = [];
            $hasPackage = false;
            foreach ($dateSlots as $slot) {
                if ($slot->booking) {
                    $slotsKeyed["{$slot->court_id}_{$slot->play_session_id}"] = $slot->booking;
                    if ($slot->booking->type === BookingType::PACKAGE && $slot->booking->status === BookingStatus::PAID) {
                        $hasPackage = true;
                    }
                }
            }

            // Hitung status mini matrix per sesi dan lapangan
            $emptyCount = 0;
            $miniMatrix = [];

            foreach ($sessions as $session) {
                $endTime = Carbon::parse("{$dateStr} {$session->end_time}", 'Asia/Jayapura');
                $isPastSession = $currentJayapura->greaterThanOrEqualTo($endTime);

                $row = [];
                foreach ($courts as $court) {
                    $isBlocked = isset($blocksKeyed["{$court->id}_{$session->id}"]) || isset($blocksKeyed["{$court->id}_all"]);
                    $booking = $slotsKeyed["{$court->id}_{$session->id}"] ?? null;

                    $cellStatus = 'kosong';
                    $isCellPackage = false;

                    if ($isBlocked) {
                        $cellStatus = 'ditutup';
                    } elseif ($booking) {
                        if ($booking->status === BookingStatus::PAID) {
                            $cellStatus = 'terisi';
                            if ($booking->type === BookingType::PACKAGE) {
                                $isCellPackage = true;
                            }
                        } elseif ($booking->status === BookingStatus::PENDING_PAYMENT) {
                            $isHoldExpired = $booking->expires_at && $booking->expires_at->lessThanOrEqualTo($currentJayapura);
                            if (!$isHoldExpired) {
                                $cellStatus = 'menunggu_bayar';
                            } else {
                                $cellStatus = $isPastSession ? 'lewat' : 'kosong';
                            }
                        } else {
                            $cellStatus = $isPastSession ? 'lewat' : 'kosong';
                        }
                    } else {
                        $cellStatus = $isPastSession ? 'lewat' : 'kosong';
                    }

                    if ($cellStatus === 'kosong') {
                        $emptyCount++;
                    }

                    $row[] = [
                        'court_id' => $court->id,
                        'session_id' => $session->id,
                        'status' => $cellStatus,
                        'is_package' => $isCellPackage,
                    ];
                }
                $miniMatrix[] = $row;
            }

            $days[] = [
                'date' => $dateStr,
                'day' => (int) $cursor->format('j'),
                'day_of_week' => (int) $cursor->dayOfWeekIso, // 1 (Senin) s/d 7 (Minggu)
                'is_today' => $isToday,
                'is_past' => $isPastDate,
                'empty_count' => $emptyCount,
                'total_slots' => $slotsPerDay,
                'has_package' => $hasPackage,
                'matrix' => $miniMatrix,
            ];

            $cursor->addDay();
        }

        // Tentukan padding awal bulan (agar kalender mulai hari Senin)
        $firstDayOfWeek = $days[0]['day_of_week'] ?? 1; // 1 = Senin, 7 = Minggu
        $startPadding = $firstDayOfWeek - 1;

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthNames[$month] ?? '',
            'title' => ($monthNames[$month] ?? '') . ' ' . $year,
            'start_padding' => $startPadding,
            'days' => $days,
            'courts_count' => $courtsCount,
            'sessions_count' => $sessionsCount,
        ];
    }
}
