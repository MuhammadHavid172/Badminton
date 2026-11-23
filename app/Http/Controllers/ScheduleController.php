<?php

namespace App\Http\Controllers;

use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Tampilan Publik Jadwal Lapangan (/jadwal).
     * Mendukung tampilan 'hari' dan 'kalender'.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $tampilan = $request->query('tampilan', 'hari');
        if (!in_array($tampilan, ['hari', 'kalender'])) {
            $tampilan = 'hari';
        }

        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->format('Y-m-d');

        // Batas validitas rentang tanggal: 1 bulan ke belakang s/d 6 bulan ke depan
        $minDate = $nowJayapura->copy()->subMonth()->startOfDay();
        $maxDate = $nowJayapura->copy()->addMonths(6)->endOfDay();

        $tanggalInput = $request->query('tanggal', $todayStr);

        // Validasi format YYYY-MM-DD
        $isValidFormat = is_string($tanggalInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalInput);
        $targetDate = null;

        if ($isValidFormat) {
            try {
                $targetDate = Carbon::createFromFormat('Y-m-d', $tanggalInput, 'Asia/Jayapura')->startOfDay();
            } catch (\Exception $e) {
                $targetDate = null;
            }
        }

        // Pengalihan jika tanggal tidak valid atau berada di luar rentang
        if (!$targetDate || $targetDate->lt($minDate) || $targetDate->gt($maxDate)) {
            return redirect()->route('jadwal', [
                'tampilan' => $tampilan,
                'tanggal' => $todayStr,
            ]);
        }

        $targetDateStr = $targetDate->format('Y-m-d');

        // Strip 7 Hari Terdekat (Jalan Pintas Navigasi)
        $stripHari = [];
        $stripStart = $nowJayapura->copy()->startOfDay();
        for ($i = 0; $i < 7; $i++) {
            $stripDate = $stripStart->copy()->addDays($i);
            $dateFormatted = $stripDate->format('Y-m-d');
            $stripHari[] = [
                'date' => $dateFormatted,
                'day_name' => tanggal_indonesia($stripDate, true, false),
                'day_short' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$stripDate->dayOfWeek],
                'day_num' => $stripDate->format('d/m'),
                'is_today' => ($dateFormatted === $todayStr),
                'is_active' => ($dateFormatted === $targetDateStr),
            ];
        }

        // Tombol Sebelumnya dan Berikutnya
        $prevDate = $targetDate->copy()->subDay();
        $nextDate = $targetDate->copy()->addDay();
        $prevDateStr = $prevDate->gte($minDate) ? $prevDate->format('Y-m-d') : null;
        $nextDateStr = $nextDate->lte($maxDate) ? $nextDate->format('Y-m-d') : null;

        // Data Tampilan Hari
        $dayData = null;
        if ($tampilan === 'hari') {
            $dayData = $this->scheduleService->dayGrid($targetDate);
        }

        // Data Tampilan Kalender
        $monthData = null;
        $selectedBulan = $request->query('bulan');
        if ($tampilan === 'kalender') {
            if ($selectedBulan && preg_match('/^\d{4}-\d{2}$/', $selectedBulan)) {
                [$cYear, $cMonth] = explode('-', $selectedBulan);
                $year = (int) $cYear;
                $month = (int) $cMonth;
            } else {
                $year = (int) $targetDate->format('Y');
                $month = (int) $targetDate->format('n');
            }

            $monthData = $this->scheduleService->monthSummary($year, $month);

            // Navigasi Bulan
            $currentMonthCarbon = Carbon::createFromDate($year, $month, 1, 'Asia/Jayapura');
            $prevMonthCarbon = $currentMonthCarbon->copy()->subMonth();
            $nextMonthCarbon = $currentMonthCarbon->copy()->addMonth();

            $monthData['prev_bulan'] = $prevMonthCarbon->format('Y-m');
            $monthData['next_bulan'] = $nextMonthCarbon->format('Y-m');
            $monthData['can_prev'] = $prevMonthCarbon->endOfMonth()->gte($minDate);
            $monthData['can_next'] = $nextMonthCarbon->startOfMonth()->lte($maxDate);
        }

        return view('jadwal.index', [
            'tampilan' => $tampilan,
            'tanggal' => $targetDateStr,
            'targetDate' => $targetDate,
            'todayStr' => $todayStr,
            'stripHari' => $stripHari,
            'prevDateStr' => $prevDateStr,
            'nextDateStr' => $nextDateStr,
            'minDateStr' => $minDate->format('Y-m-d'),
            'maxDateStr' => $maxDate->format('Y-m-d'),
            'dayData' => $dayData,
            'monthData' => $monthData,
        ]);
    }

    /**
     * API JSON: Status Slot Harian (/api/jadwal/hari?tanggal=).
     * Bebas data pribadi, throttle dan cache 10 detik.
     */
    public function apiDay(Request $request): JsonResponse
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->format('Y-m-d');
        $tanggal = $request->query('tanggal', $todayStr);

        if (!is_string($tanggal) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $tanggal = $todayStr;
        }

        $cacheKey = "api_schedule_day_{$tanggal}";

        $data = Cache::remember($cacheKey, 10, function () use ($tanggal) {
            return $this->scheduleService->dayGrid($tanggal);
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * API JSON: Ringkasan Bulanan (/api/jadwal/bulan?bulan=YYYY-MM).
     * Bebas data pribadi, throttle dan cache 10 detik.
     */
    public function apiMonth(Request $request): JsonResponse
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $bulanParam = $request->query('bulan', $nowJayapura->format('Y-m'));

        if (!is_string($bulanParam) || !preg_match('/^\d{4}-\d{2}$/', $bulanParam)) {
            $bulanParam = $nowJayapura->format('Y-m');
        }

        [$year, $month] = explode('-', $bulanParam);
        $cacheKey = "api_schedule_month_{$year}_{$month}";

        $data = Cache::remember($cacheKey, 10, function () use ($year, $month) {
            return $this->scheduleService->monthSummary((int) $year, (int) $month);
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
