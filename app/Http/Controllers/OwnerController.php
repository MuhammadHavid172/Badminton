<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DashboardService;
use App\Services\PackageLifecycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerController extends Controller
{
    /**
     * Hitung jumlah jatah bulanan yang perlu ditindaklanjuti.
     */
    protected function getJatahHabisCount(): int
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->toDateString();
        $fourteenDaysAhead = $nowJayapura->copy()->addDays(14)->toDateString();
        $sevenDaysAgo = $nowJayapura->copy()->subDays(7)->toDateString();

        $bookings = Booking::where('type', BookingType::PACKAGE)
            ->where('status', BookingStatus::PAID)
            ->with(['slots.session'])
            ->get();

        $count = 0;
        foreach ($bookings as $b) {
            $lc = new PackageLifecycle($b);
            if ($lc->continuationStatus($nowJayapura) === 'sudah_lanjut') {
                continue;
            }

            $lastDate = $lc->lastPlayDate();
            $remaining = $lc->remainingMeetings($nowJayapura);

            $isNearEnd = ($remaining <= 1 || ($lastDate && $lastDate <= $fourteenDaysAhead && $lastDate >= $todayStr));
            $isRecentEnd = ($lastDate && $lastDate >= $sevenDaysAgo && $lastDate < $todayStr && $remaining === 0);

            if ($isNearEnd || $isRecentEnd) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Landing Owner = Ringkasan Eksekutif Mode Pantau (GET /owner).
     */
    public function index(DashboardService $dashboardService)
    {
        return $this->ringkasan($dashboardService);
    }

    /**
     * Ringkasan & Grafik Okupansi Mode Pantau (GET /owner/ringkasan).
     */
    public function ringkasan(DashboardService $dashboardService)
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->toDateString();

        $stats = $dashboardService->getSummaryStats($nowJayapura);
        $chartDays = $dashboardService->getOccupancyChartData($nowJayapura);

        // Jatah Bulanan hampir habis (top 5)
        $fourteenDaysAhead = $nowJayapura->copy()->addDays(14)->toDateString();
        $sevenDaysAgo = $nowJayapura->copy()->subDays(7)->toDateString();

        $allBulanan = Booking::where('type', BookingType::PACKAGE)
            ->where('status', BookingStatus::PAID)
            ->with(['slots.court', 'slots.session', 'user'])
            ->get();

        $criticalBulanan = collect();
        foreach ($allBulanan as $b) {
            $lc = new PackageLifecycle($b);
            if ($lc->continuationStatus($nowJayapura) === 'sudah_lanjut') {
                continue;
            }
            $lastDate = $lc->lastPlayDate();
            $remaining = $lc->remainingMeetings($nowJayapura);

            $isNearEnd = ($remaining <= 1 || ($lastDate && $lastDate <= $fourteenDaysAhead && $lastDate >= $todayStr));
            $isRecentEnd = ($lastDate && $lastDate >= $sevenDaysAgo && $lastDate < $todayStr && $remaining === 0);

            if ($isNearEnd || $isRecentEnd) {
                $criticalBulanan->push([
                    'booking' => $b,
                    'user' => $b->user,
                    'court' => $lc->lastSlot()?->court,
                    'session' => $lc->lastSlot()?->session,
                    'last_date' => $lastDate,
                    'remaining' => $remaining,
                ]);
            }
        }
        $criticalBulanan = $criticalBulanan->take(5);

        // Penutupan slot mendatang
        $upcomingClosures = SlotBlock::with(['court', 'session', 'creator'])
            ->where('play_date', '>=', $todayStr)
            ->orderBy('play_date')
            ->take(5)
            ->get();

        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.ringkasan', compact(
            'stats',
            'chartDays',
            'criticalBulanan',
            'upcomingClosures',
            'jatahHabisCount',
            'modePantau'
        ));
    }

    /**
     * Jadwal Operasional Hari Ini Mode Pantau (GET /owner/hari-ini).
     */
    public function hariIni(Request $request)
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->toDateString();
        $currentTimeStr = $nowJayapura->format('H:i:s');

        $searchQuery = trim($request->get('q', ''));
        $searchResults = null;

        if (!empty($searchQuery)) {
            $searchResults = Booking::with(['slots.court', 'slots.session', 'user'])
                ->where('code', 'like', "%{$searchQuery}%")
                ->orWhereHas('user', function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%")
                      ->orWhere('phone', 'like', "%{$searchQuery}%")
                      ->orWhere('team_name', 'like', "%{$searchQuery}%");
                })
                ->latest()
                ->take(15)
                ->get();
        }

        $courts = Court::where('is_active', true)->orderBy('sort_order')->get();
        $sessions = PlaySession::where('is_active', true)->orderBy('sort_order')->get();

        $slotsToday = BookingSlot::with(['booking.user', 'court', 'session'])
            ->where('play_date', $todayStr)
            ->get()
            ->keyBy(fn($s) => "{$s->court_id}_{$s->play_session_id}");

        $blocksToday = SlotBlock::where('play_date', $todayStr)->get();

        $currentSessions = collect();
        $upcomingSessions = collect();
        $pastSessions = collect();

        foreach ($sessions as $session) {
            $isCurrent = ($currentTimeStr >= $session->start_time && $currentTimeStr < $session->end_time);
            $isPast = ($currentTimeStr >= $session->end_time);
            $isUpcoming = ($currentTimeStr < $session->start_time);

            $courtItems = [];
            foreach ($courts as $court) {
                $block = $blocksToday->first(fn($b) => $b->court_id === $court->id && (is_null($b->play_session_id) || $b->play_session_id === $session->id));
                $slot = $slotsToday->get("{$court->id}_{$session->id}");

                $courtItems[] = [
                    'court' => $court,
                    'slot' => $slot,
                    'block' => $block,
                ];
            }

            $sessionData = [
                'session' => $session,
                'is_current' => $isCurrent,
                'is_past' => $isPast,
                'is_upcoming' => $isUpcoming,
                'court_items' => $courtItems,
            ];

            if ($isCurrent) {
                $currentSessions->push($sessionData);
            } elseif ($isUpcoming) {
                $upcomingSessions->push($sessionData);
            } else {
                $pastSessions->push($sessionData);
            }
        }

        $orderedSessions = $currentSessions->concat($upcomingSessions)->concat($pastSessions);
        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.hari-ini', compact(
            'orderedSessions',
            'todayStr',
            'nowJayapura',
            'searchQuery',
            'searchResults',
            'jatahHabisCount',
            'modePantau'
        ));
    }

    /**
     * Jatah Bulanan Hampir Habis Mode Pantau (GET /owner/jatah-habis).
     */
    public function jatahHabis(Request $request)
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $todayStr = $nowJayapura->toDateString();
        $fourteenDaysAhead = $nowJayapura->copy()->addDays(14)->toDateString();
        $sevenDaysAgo = $nowJayapura->copy()->subDays(7)->toDateString();

        $allBulanan = Booking::where('type', BookingType::PACKAGE)
            ->where('status', BookingStatus::PAID)
            ->with(['slots.court', 'slots.session', 'user'])
            ->get();

        $actionList = collect();

        foreach ($allBulanan as $b) {
            $lc = new PackageLifecycle($b);
            $contStatus = $lc->continuationStatus($nowJayapura);

            if ($contStatus === 'sudah_lanjut') {
                continue;
            }

            $lastDate = $lc->lastPlayDate();
            $remaining = $lc->remainingMeetings($nowJayapura);

            $isNearEnd = ($remaining <= 1 || ($lastDate && $lastDate <= $fourteenDaysAhead && $lastDate >= $todayStr));
            $isRecentEnd = ($lastDate && $lastDate >= $sevenDaysAgo && $lastDate < $todayStr && $remaining === 0);

            if ($isNearEnd || $isRecentEnd) {
                $lastSlot = $lc->lastSlot();
                $court = $lastSlot?->court;
                $session = $lastSlot?->session;
                $dayName = $lastSlot ? Carbon::parse($lastSlot->play_date)->locale('id')->isoFormat('dddd') : '';

                $reminderText = __('admin.reminder_text', [
                    'nama' => $b->user?->name ?? 'Kak',
                    'lapangan' => $court?->name ?? 'Lapangan',
                    'hari' => $dayName,
                    'sesi' => $session?->formattedRange() ?? '',
                    'sisa' => $remaining,
                    'tanggal' => $lastDate ? tanggal_indonesia($lastDate, false, false) : '',
                ]);

                $actionList->push([
                    'booking' => $b,
                    'user' => $b->user,
                    'court' => $court,
                    'session' => $session,
                    'last_date' => $lastDate,
                    'remaining' => $remaining,
                    'continuation_status' => $contStatus,
                    'reminder_text' => $reminderText,
                ]);
            }
        }

        $jatahHabisCount = $actionList->count();
        $modePantau = true;

        return view('admin.jatah-habis', compact('actionList', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Daftar Semua Booking Mode Pantau (GET /owner/booking).
     */
    public function booking(Request $request)
    {
        $query = Booking::with(['slots.court', 'slots.session', 'user', 'payments'])
            ->latest('id');

        if ($request->filled('dari')) {
            $query->whereHas('slots', fn($q) => $q->where('play_date', '>=', $request->dari));
        }
        if ($request->filled('sampai')) {
            $query->whereHas('slots', fn($q) => $q->where('play_date', '<=', $request->sampai));
        }
        if ($request->filled('court_id')) {
            $query->whereHas('slots', fn($q) => $q->where('court_id', $request->court_id));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('q')) {
            $qText = $request->q;
            $query->where(function ($sub) use ($qText) {
                $sub->where('code', 'like', "%{$qText}%")
                    ->orWhereHas('user', function ($u) use ($qText) {
                        $u->where('name', 'like', "%{$qText}%")
                          ->orWhere('phone', 'like', "%{$qText}%")
                          ->orWhere('team_name', 'like', "%{$qText}%");
                    });
            });
        }

        $bookings = $query->paginate(20)->withQueryString();
        $courts = Court::where('is_active', true)->orderBy('sort_order')->get();
        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.booking.index', compact('bookings', 'courts', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Detail Booking Mode Pantau (GET /owner/booking/{code}).
     */
    public function showBooking(string $code)
    {
        $booking = Booking::with(['slots.court', 'slots.session', 'user', 'payments', 'cancelledByUser'])
            ->where('code', $code)
            ->firstOrFail();

        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.booking.detail', compact('booking', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Daftar Pengguna Mode Pantau (GET /owner/pengguna).
     */
    public function pengguna(Request $request)
    {
        $query = User::withCount('bookings')->latest('id');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'aktif';
            $query->where('is_active', $isActive);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('team_name', 'like', "%{$q}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();
        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.pengguna.index', compact('users', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Detail Pengguna & Riwayat Booking Mode Pantau (GET /owner/pengguna/{id}).
     */
    public function showPengguna(int $id)
    {
        $user = User::withCount('bookings')->findOrFail($id);
        $bookings = Booking::with(['slots.court', 'slots.session', 'payments'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(15);

        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.pengguna.show', compact('user', 'bookings', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Log Aktivitas Mode Pantau (GET /owner/aktivitas).
     */
    public function aktivitas(Request $request)
    {
        $query = ActivityLog::with('user')->latest('id');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('dari')) {
            $query->whereDate('created_at', '>=', $request->dari);
        }

        if ($request->filled('sampai')) {
            $query->whereDate('created_at', '<=', $request->sampai);
        }

        $logs = $query->paginate(25)->withQueryString();
        $distinctActions = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.aktivitas', compact('logs', 'distinctActions', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Laporan Operasional & Keuangan Mode Pantau (GET /owner/laporan).
     */
    public function laporan(Request $request, DashboardService $dashboardService)
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $dari = $request->get('dari', $nowJayapura->copy()->subDays(30)->toDateString());
        $sampai = $request->get('sampai', $nowJayapura->toDateString());

        $reportData = $dashboardService->getReportData($dari, $sampai, $nowJayapura);
        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.laporan', compact('reportData', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Ekspor CSV Laporan Transaksi Aman (GET /owner/laporan/ekspor-csv).
     */
    public function laporanEksporCsv(Request $request): StreamedResponse
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $dari = $request->get('dari', $nowJayapura->copy()->subDays(30)->toDateString());
        $sampai = $request->get('sampai', $nowJayapura->toDateString());

        $start = Carbon::parse($dari, 'Asia/Jayapura')->startOfDay();
        $end = Carbon::parse($sampai, 'Asia/Jayapura')->endOfDay();

        $payments = Payment::with(['booking.user', 'booking.slots.court', 'booking.slots.session'])
            ->whereBetween('paid_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->latest('paid_at')
            ->get();

        ActivityLogger::log(
            'laporan.csv_export',
            "Owner mengekspor CSV laporan transaksi dari {$dari} s.d. {$sampai}",
            null,
            ['count' => $payments->count(), 'dari' => $dari, 'sampai' => $sampai],
            Auth::user()
        );

        $filename = "laporan-transaksi-{$dari}-sd-{$sampai}.csv";

        $cleanCell = function ($val): string {
            $str = (string) $val;
            if (str_starts_with($str, '=') || str_starts_with($str, '+') || str_starts_with($str, '-') || str_starts_with($str, '@')) {
                return "'" . $str;
            }
            return $str;
        };

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($payments, $cleanCell) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Waktu Pembayaran',
                'Kode Booking',
                'Nama Pemesan',
                'Nomor HP',
                'PB / Tim',
                'Kategori',
                'Metode Bayar',
                'Jumlah (Rp)',
                'Status Transaksi',
            ]);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $cleanCell($p->paid_at ? Carbon::parse($p->paid_at)->timezone('Asia/Jayapura')->format('Y-m-d H:i:s') : ''),
                    $cleanCell($p->booking?->code),
                    $cleanCell($p->booking?->user?->name),
                    $cleanCell($p->booking?->user?->phone),
                    $cleanCell($p->booking?->user?->team_name),
                    $cleanCell($p->booking?->type?->label()),
                    $cleanCell(strtoupper($p->method)),
                    $cleanCell($p->amount),
                    $cleanCell($p->status->label()),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Nilai Pengaturan Operasional Sebagai Teks Mode Pantau (GET /owner/pengaturan).
     */
    public function pengaturan()
    {
        $settings = [
            'price_single' => Setting::getInt('price_single', 200000),
            'price_package' => Setting::getInt('price_package', 600000),
            'hold_minutes' => Setting::getInt('hold_minutes', 15),
            'max_pending_bookings' => Setting::getInt('max_pending_bookings', 2),
            'renew_notice_remaining' => Setting::getInt('renew_notice_remaining', 1),
            'last_day_notice_hour' => Setting::getInt('last_day_notice_hour', 6),
        ];

        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = true;

        return view('admin.pengaturan', compact('settings', 'jatahHabisCount', 'modePantau'));
    }
}
