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
use App\Models\SlotBlock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Hitung ringkasan angka tipografis hari ini (zona Asia/Jayapura).
     *
     * @return array{
     *     pendapatan_hari_ini: int,
     *     pendapatan_bulan_ini: int,
     *     booking_hari_ini: int,
     *     okupansi_hari_ini_persen: int,
     *     slot_terisi_hari_ini: int,
     *     total_kapasitas_hari_ini: int,
     *     booking_menunggu_bayar: int
     * }
     */
    public function getSummaryStats(Carbon $nowJayapura): array
    {
        $todayStr = $nowJayapura->toDateString();
        $startOfDay = $nowJayapura->copy()->startOfDay()->toDateTimeString();
        $endOfDay = $nowJayapura->copy()->endOfDay()->toDateTimeString();
        $startOfMonth = $nowJayapura->copy()->startOfMonth()->toDateTimeString();
        $endOfMonth = $nowJayapura->copy()->endOfMonth()->toDateTimeString();

        // 1. Pendapatan Hari Ini (dari Payment PAID berdasarkan paid_at di zona Jayapura)
        $pendapatanHariIni = (int) Payment::where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$startOfDay, $endOfDay])
            ->sum('amount');

        // 2. Pendapatan Bulan Ini (dari Payment PAID berdasarkan paid_at di zona Jayapura)
        $pendapatanBulanIni = (int) Payment::where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // 3. Jumlah Booking Hari Ini (booking yang dibuat hari ini di zona Jayapura)
        $bookingHariIni = Booking::whereBetween('created_at', [$startOfDay, $endOfDay])->count();

        // 4. Okupansi Hari Ini
        $totalCourts = Court::where('is_active', true)->count();
        $totalSessions = PlaySession::where('is_active', true)->count();
        $totalKapasitasHariIni = $totalCourts * $totalSessions;

        // Slot terisi hari ini (booking PAID atau PENDING belum expired, plus slot block)
        $bookedSlotsCount = BookingSlot::where('play_date', $todayStr)
            ->whereHas('booking', function ($q) use ($nowJayapura) {
                $q->where('status', BookingStatus::PAID)
                    ->orWhere(function ($sub) use ($nowJayapura) {
                        $sub->where('status', BookingStatus::PENDING_PAYMENT)
                            ->where('expires_at', '>', $nowJayapura);
                    });
            })->count();

        $blockedSlotsCount = SlotBlock::where('play_date', $todayStr)
            ->where(function ($q) {
                $q->whereNull('play_session_id')
                    ->orWhereNotNull('play_session_id');
            })->count();

        // Slot terisi tidak melebihi kapasitas
        $slotTerisiHariIni = min($totalKapasitasHariIni, $bookedSlotsCount + $blockedSlotsCount);
        $okupansiPersen = $totalKapasitasHariIni > 0
            ? (int) round(($slotTerisiHariIni / $totalKapasitasHariIni) * 100)
            : 0;

        // 5. Booking Menunggu Bayar (pending_payment yang belum expired)
        $bookingMenungguBayar = Booking::where('status', BookingStatus::PENDING_PAYMENT)
            ->where('expires_at', '>', $nowJayapura)
            ->count();

        return [
            'pendapatan_hari_ini' => $pendapatanHariIni,
            'pendapatan_bulan_ini' => $pendapatanBulanIni,
            'booking_hari_ini' => $bookingHariIni,
            'okupansi_hari_ini_persen' => $okupansiPersen,
            'slot_terisi_hari_ini' => $slotTerisiHariIni,
            'total_kapasitas_hari_ini' => $totalKapasitasHariIni,
            'booking_menunggu_bayar' => $bookingMenungguBayar,
        ];
    }

    /**
     * Hitung data okupansi 29 hari (-14 hari s.d. +14 hari) untuk grafik batang SVG.
     *
     * @return array<int, array{
     *     date: string,
     *     label_day: string,
     *     is_today: bool,
     *     is_past: bool,
     *     is_future: bool,
     *     occupied: int,
     *     capacity: int,
     *     percentage: int
     * }>
     */
    public function getOccupancyChartData(Carbon $nowJayapura): array
    {
        $totalCourts = Court::where('is_active', true)->count();
        $totalSessions = PlaySession::where('is_active', true)->count();
        $capacityPerDay = max(1, $totalCourts * $totalSessions);

        $startDate = $nowJayapura->copy()->subDays(14)->startOfDay();
        $endDate = $nowJayapura->copy()->addDays(14)->endOfDay();
        $todayStr = $nowJayapura->toDateString();

        // Ambil semua slot booking aktif dalam rentang tanggal
        $bookedSlots = BookingSlot::whereBetween('play_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('booking', function ($q) use ($nowJayapura) {
                $q->where('status', BookingStatus::PAID)
                    ->orWhere(function ($sub) use ($nowJayapura) {
                        $sub->where('status', BookingStatus::PENDING_PAYMENT)
                            ->where('expires_at', '>', $nowJayapura);
                    });
            })
            ->select('play_date', DB::raw('count(*) as total'))
            ->groupBy('play_date')
            ->pluck('total', 'play_date')
            ->all();

        // Ambil semua slot block dalam rentang tanggal
        $blockedSlots = SlotBlock::whereBetween('play_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('play_date', DB::raw('count(*) as total'))
            ->groupBy('play_date')
            ->pluck('total', 'play_date')
            ->all();

        $chartDays = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $dateStr = $cursor->toDateString();
            $isToday = ($dateStr === $todayStr);
            $isPast = ($cursor->lt($nowJayapura->copy()->startOfDay()));
            $isFuture = ($cursor->gt($nowJayapura->copy()->endOfDay()));

            $bCount = $bookedSlots[$dateStr] ?? 0;
            $blkCount = $blockedSlots[$dateStr] ?? 0;
            $occupied = min($capacityPerDay, $bCount + $blkCount);
            $percentage = (int) round(($occupied / $capacityPerDay) * 100);

            $chartDays[] = [
                'date' => $dateStr,
                'label_day' => $cursor->format('d'),
                'is_today' => $isToday,
                'is_past' => $isPast,
                'is_future' => $isFuture,
                'occupied' => $occupied,
                'capacity' => $capacityPerDay,
                'percentage' => $percentage,
            ];

            $cursor->addDay();
        }

        return $chartDays;
    }

    /**
     * Hitung metrik laporan keuangan, kategori, metode bayar, dan tingkat perpanjangan Bulanan.
     */
    public function getReportData(string $dari, string $sampai, Carbon $nowJayapura): array
    {
        $start = Carbon::parse($dari, 'Asia/Jayapura')->startOfDay();
        $end = Carbon::parse($sampai, 'Asia/Jayapura')->endOfDay();

        // 1. Total Pendapatan
        $totalPendapatan = (int) Payment::where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->sum('amount');

        // 2. Jumlah Booking per Kategori dalam rentang tanggal
        $bookingsInRange = Booking::whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->where('status', '!=', BookingStatus::CANCELLED)
            ->get();

        $countHarian = $bookingsInRange->where('type', BookingType::SINGLE)->count();
        $countBulanan = $bookingsInRange->where('type', BookingType::PACKAGE)->count();
        $totalBooking = $bookingsInRange->count();

        // 3. Rincian per Metode Bayar
        $paymentMethods = Payment::where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->select('method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('method')
            ->get();

        // 4. Tingkat Perpanjangan Bulanan (Renewal Rate)
        // Cari seluruh booking Bulanan PAID yang pertemuan ke-4 (terakhir) jatuh di rentang $dari sd $sampai
        $allPackages = Booking::where('type', BookingType::PACKAGE)
            ->where('status', BookingStatus::PAID)
            ->with(['slots.court', 'slots.session', 'user'])
            ->get();

        $endedPackages = collect();
        $renewedPackages = collect();
        $unrenewedPackages = collect();

        foreach ($allPackages as $pkg) {
            $lc = new PackageLifecycle($pkg);
            $lastPlayDate = $lc->lastPlayDate();

            if ($lastPlayDate && $lastPlayDate >= $dari && $lastPlayDate <= $sampai) {
                $endedPackages->push($pkg);

                // Cek apakah ada booking yang renewed_from_id-nya mengarah ke booking ini dan berstatus paid
                $isRenewed = Booking::where('renewed_from_id', $pkg->id)
                    ->where('status', BookingStatus::PAID)
                    ->exists();

                if ($isRenewed) {
                    $renewedPackages->push($pkg);
                } else {
                    $unrenewedPackages->push([
                        'booking' => $pkg,
                        'user' => $pkg->user,
                        'last_date' => $lastPlayDate,
                        'court' => $lc->lastSlot()?->court,
                        'session' => $lc->lastSlot()?->session,
                    ]);
                }
            }
        }

        $totalEnded = $endedPackages->count();
        $totalRenewed = $renewedPackages->count();
        $renewalRatePercent = $totalEnded > 0
            ? (int) round(($totalRenewed / $totalEnded) * 100)
            : 0;

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'total_pendapatan' => $totalPendapatan,
            'total_booking' => $totalBooking,
            'count_harian' => $countHarian,
            'count_bulanan' => $countBulanan,
            'payment_methods' => $paymentMethods,
            'total_ended_bulanan' => $totalEnded,
            'total_renewed_bulanan' => $totalRenewed,
            'renewal_rate_percent' => $renewalRatePercent,
            'unrenewed_packages' => $unrenewedPackages,
        ];
    }
}
