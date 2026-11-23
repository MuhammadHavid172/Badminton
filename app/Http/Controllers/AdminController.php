<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Services\ActivityLogger;
use App\Services\BookingService;
use App\Services\DashboardService;
use App\Services\PackageLifecycle;
use App\Services\RenewalAlternatives;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

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
            ->with(['slots.session', 'slots.court'])
            ->get();

        $count = 0;
        foreach ($bookings as $b) {
            $lc = new PackageLifecycle($b);
            if ($lc->continuationStatus($nowJayapura) === 'sudah_lanjut') {
                continue;
            }

            $lastDate = $lc->lastPlayDate();
            $remaining = $lc->remainingMeetings($nowJayapura);

            // Hampir habis/terakhir dalam 14 hari ke depan ATAU baru berakhir dalam 7 hari terakhir
            $isNearEnd = ($remaining <= 1 || ($lastDate && $lastDate <= $fourteenDaysAhead && $lastDate >= $todayStr));
            $isRecentEnd = ($lastDate && $lastDate >= $sevenDaysAgo && $lastDate < $todayStr && $remaining === 0);

            if ($isNearEnd || $isRecentEnd) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Landing /admin = "Hari Ini": daftar per sesi (mobile-first, area sentuh 48px).
     */
    public function index(Request $request)
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

        // Ambil seluruh slot booking dan slot block untuk hari ini
        $slotsToday = BookingSlot::with(['booking.user', 'booking.slots.session', 'court', 'session'])
            ->where('play_date', $todayStr)
            ->get()
            ->keyBy(fn($s) => "{$s->court_id}_{$s->play_session_id}");

        $blocksToday = SlotBlock::where('play_date', $todayStr)->get();

        // Kelompokkan sesi: Sedang Berlangsung di atas, lalu Berikutnya, lalu Sudah Lewat
        $currentSessions = collect();
        $upcomingSessions = collect();
        $pastSessions = collect();

        foreach ($sessions as $session) {
            $isCurrent = ($currentTimeStr >= $session->start_time && $currentTimeStr < $session->end_time);
            $isPast = ($currentTimeStr >= $session->end_time);
            $isUpcoming = ($currentTimeStr < $session->start_time);

            // Susun item per lapangan dalam sesi ini
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

        // Urutan akhir: Sedang Berlangsung -> Sesi Berikutnya -> Sesi Lewat (diredupkan)
        $orderedSessions = $currentSessions->concat($upcomingSessions)->concat($pastSessions);
        $jatahHabisCount = $this->getJatahHabisCount();

        return view('admin.hari-ini', compact(
            'orderedSessions',
            'todayStr',
            'nowJayapura',
            'searchQuery',
            'searchResults',
            'jatahHabisCount'
        ));
    }

    /**
     * Check-in kehadiran pelanggan (POST /admin/slot/{id}/check-in).
     */
    public function checkIn(Request $request, int $slotId)
    {
        $slot = BookingSlot::with('booking.user')->findOrFail($slotId);

        $nowJayapura = Carbon::now('Asia/Jayapura');
        $slot->update([
            'checked_in_at' => $nowJayapura,
            'checked_in_by' => Auth::id(),
            'attendance_note' => $request->attendance_note,
        ]);

        ActivityLogger::log(
            'slot.check_in',
            "Check-in kehadiran untuk booking {$slot->booking->code} pada {$slot->court->name} ({$slot->play_date->format('Y-m-d')})",
            $slot,
            ['attendance_note' => $request->attendance_note],
            Auth::user()
        );

        return redirect()->back()->with('status', 'Kehadiran berhasil dicatat.');
    }

    /**
     * Batalkan check-in kehadiran (POST /admin/slot/{id}/batal-check-in).
     */
    public function cancelCheckIn(Request $request, int $slotId)
    {
        $slot = BookingSlot::with('booking')->findOrFail($slotId);

        $slot->update([
            'checked_in_at' => null,
            'checked_in_by' => null,
            'attendance_note' => null,
        ]);

        ActivityLogger::log(
            'slot.cancel_check_in',
            "Pembatalan check-in kehadiran untuk booking {$slot->booking->code}",
            $slot,
            [],
            Auth::user()
        );

        return redirect()->back()->with('status', 'Catatan kehadiran berhasil dibatalkan.');
    }

    /**
     * Detail booking operasional admin (GET /admin/booking/{code}).
     */
    public function showBooking(string $code)
    {
        $booking = Booking::with(['slots.court', 'slots.session', 'user', 'payments', 'cancelledByUser'])
            ->where('code', $code)
            ->firstOrFail();

        $jatahHabisCount = $this->getJatahHabisCount();

        return view('admin.booking.detail', compact('booking', 'jatahHabisCount'));
    }

    /**
     * Pembatalan booking oleh admin (POST /admin/booking/{code}/batal).
     */
    public function cancelBooking(Request $request, string $code)
    {
        $booking = Booking::with(['slots.court', 'slots.session', 'user'])->where('code', $code)->firstOrFail();

        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'refund_note' => ['nullable', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        // Jika booking berstatus PAID, wajib konfirmasi password admin
        if ($booking->status === BookingStatus::PAID) {
            $request->validate([
                'password' => ['required', 'string'],
            ], [
                'password.required' => 'Konfirmasi password diperlukan untuk membatalkan booking berbayar.',
            ]);

            if (!Hash::check($request->password, Auth::user()->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Password konfirmasi tidak sesuai.',
                ]);
            }
        }

        DB::transaction(function () use ($booking, $request) {
            $nowJayapura = Carbon::now('Asia/Jayapura');

            // Membebaskan SEMUA slot di masa depan; slot masa lalu dipertahankan sebagai riwayat
            foreach ($booking->slots as $slot) {
                $sessionEnd = Carbon::parse("{$slot->play_date->format('Y-m-d')} {$slot->session->end_time}", 'Asia/Jayapura');
                if ($sessionEnd->gt($nowJayapura)) {
                    $slot->delete();
                }
            }

            $booking->update([
                'status' => BookingStatus::CANCELLED,
                'cancelled_at' => $nowJayapura,
                'cancelled_by' => Auth::id(),
                'cancel_reason' => $request->reason,
                'refund_note' => $request->refund_note,
            ]);

            Payment::where('booking_id', $booking->id)
                ->where('status', PaymentStatus::PENDING)
                ->update(['status' => PaymentStatus::FAILED]);

            ActivityLogger::log(
                'booking.admin_cancelled',
                "Admin membatalkan booking {$booking->code} dengan alasan: {$request->reason}",
                $booking,
                ['refund_note' => $request->refund_note],
                Auth::user()
            );

            // Kirim notifikasi ke pemesan
            if ($booking->user) {
                $booking->user->notify(new BookingCancelledNotification($booking, $request->reason, $request->refund_note));
            }
        });

        return redirect()->route('admin.booking.detail', $booking->code)
            ->with('status', 'Booking berhasil dibatalkan. Slot masa depan telah dibebaskan.');
    }

    /**
     * Halaman Jatah Habis (/admin/jatah-habis).
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

            // Abaikan jika sudah lanjut
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

                // Buat teks pesan pengingat siap pakai dari lang/id/admin.php
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

        return view('admin.jatah-habis', compact('actionList', 'jatahHabisCount'));
    }

    /**
     * Perpanjang sewa Bulanan atas nama pelanggan oleh admin (POST /admin/booking/{code}/perpanjang-atas-nama).
     */
    public function perpanjangAtasNama(Request $request, string $code, RenewalAlternatives $renewalAlternatives)
    {
        $originalBooking = Booking::with(['slots.court', 'slots.session', 'user'])->where('code', $code)->firstOrFail();

        $request->validate([
            'metode' => ['required', 'in:tunai,tahan'],
        ]);

        $nowJayapura = Carbon::now('Asia/Jayapura');
        $alternatives = $renewalAlternatives->findAlternatives($originalBooking, $nowJayapura);

        if (!$alternatives['available']) {
            return redirect()->back()->with('error', 'Slot lanjutan untuk 4 minggu ke depan bentrok dan tidak tersedia.');
        }

        $startDate = $alternatives['target_dates'][0]['date'];
        $court = $alternatives['court'];
        $session = $alternatives['session'];
        $isTunai = ($request->metode === 'tunai');

        $newBooking = DB::transaction(function () use ($originalBooking, $court, $session, $startDate, $isTunai) {
            $newBooking = $this->bookingService->createBooking(
                $originalBooking->user,
                $court,
                $session,
                $startDate,
                BookingType::PACKAGE,
                "Perpanjangan Bulanan atas nama oleh admin (dari {$originalBooking->code})"
            );

            $newBooking->update([
                'renewed_from_id' => $originalBooking->id,
                'created_by' => Auth::id(),
            ]);

            if ($isTunai) {
                $newBooking->update(['status' => BookingStatus::PAID]);

                Payment::where('booking_id', $newBooking->id)->update([
                    'method' => 'tunai',
                    'status' => PaymentStatus::PAID,
                    'paid_at' => now(),
                    'recorded_by' => Auth::id(),
                ]);

                ActivityLogger::log(
                    'booking.admin_renewed_cash',
                    "Admin memperpanjang booking Bulanan {$newBooking->code} (Tunai Lunas)",
                    $newBooking,
                    ['amount' => $newBooking->total_price],
                    Auth::user()
                );
            } else {
                ActivityLogger::log(
                    'booking.admin_renewed_hold',
                    "Admin memperpanjang booking Bulanan {$newBooking->code} (Tahan Dulu)",
                    $newBooking,
                    [],
                    Auth::user()
                );
            }

            return $newBooking;
        });

        return redirect()->route('admin.booking.detail', $newBooking->code)
            ->with('status', 'Perpanjangan atas nama berhasil dibuat.');
    }

    /**
     * Form Booking Langsung oleh admin (GET /admin/booking-langsung).
     */
    public function bookingLangsungForm(Request $request)
    {
        $courts = Court::where('is_active', true)->orderBy('sort_order')->get();
        $sessions = PlaySession::where('is_active', true)->orderBy('sort_order')->get();
        $customers = User::where('role', UserRole::CUSTOMER)->orderBy('name')->take(50)->get();

        $selectedCourtId = $request->get('lapangan');
        $selectedSessionId = $request->get('sesi');
        $selectedDate = $request->get('tanggal', Carbon::now('Asia/Jayapura')->format('Y-m-d'));

        $jatahHabisCount = $this->getJatahHabisCount();

        return view('admin.booking-langsung', compact(
            'courts',
            'sessions',
            'customers',
            'selectedCourtId',
            'selectedSessionId',
            'selectedDate',
            'jatahHabisCount'
        ));
    }

    /**
     * Simpan Booking Langsung oleh admin (POST /admin/booking-langsung).
     */
    public function bookingLangsungStore(Request $request)
    {
        $validated = $request->validate([
            'tipe_pelanggan' => ['required', 'in:lama,baru'],
            'user_id' => ['required_if:tipe_pelanggan,lama', 'nullable', 'exists:users,id'],
            'nama_baru' => ['required_if:tipe_pelanggan,baru', 'nullable', 'string', 'max:100'],
            'hp_baru' => ['required_if:tipe_pelanggan,baru', 'nullable', 'string', 'regex:/^[0-9\+\-\s]{10,16}$/'],
            'team_name_baru' => ['nullable', 'string', 'max:100'],
            'lapangan' => ['required', 'integer', 'exists:courts,id'],
            'sesi' => ['required', 'integer', 'exists:play_sessions,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', 'in:single,package'],
            'metode_bayar' => ['required', 'in:tunai,tahan'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Tentukan pelanggan
        if ($validated['tipe_pelanggan'] === 'baru') {
            $normalizedPhone = normalisasi_nomor_hp($validated['hp_baru']);

            $existingUser = User::where('phone', $normalizedPhone)->first();
            if ($existingUser) {
                $user = $existingUser;
            } else {
                $user = User::create([
                    'name' => $validated['nama_baru'],
                    'phone' => $normalizedPhone,
                    'team_name' => $validated['team_name_baru'] ?? null,
                    'password' => Hash::make(Str::random(12)),
                    'role' => UserRole::CUSTOMER,
                    'is_active' => true,
                ]);
            }
        } else {
            $user = User::findOrFail($validated['user_id']);
        }

        $court = Court::findOrFail($validated['lapangan']);
        $session = PlaySession::findOrFail($validated['sesi']);
        $type = BookingType::from($validated['type']);
        $isTunai = ($validated['metode_bayar'] === 'tunai');

        $booking = DB::transaction(function () use ($user, $court, $session, $validated, $type, $isTunai) {
            $b = $this->bookingService->createBooking(
                $user,
                $court,
                $session,
                $validated['tanggal'],
                $type,
                $validated['notes'] ?? null
            );

            $b->update(['created_by' => Auth::id()]);

            if ($isTunai) {
                $b->update(['status' => BookingStatus::PAID]);

                Payment::where('booking_id', $b->id)->update([
                    'method' => 'tunai',
                    'status' => PaymentStatus::PAID,
                    'paid_at' => now(),
                    'recorded_by' => Auth::id(),
                ]);

                ActivityLogger::log(
                    'booking.direct_cash',
                    "Booking langsung tunai {$b->code} dibuat oleh admin untuk {$user->name}",
                    $b,
                    ['amount' => $b->total_price],
                    Auth::user()
                );
            } else {
                ActivityLogger::log(
                    'booking.direct_hold',
                    "Booking langsung tahan {$b->code} dibuat oleh admin untuk {$user->name}",
                    $b,
                    [],
                    Auth::user()
                );
            }

            return $b;
        });

        return redirect()->route('admin.booking.detail', $booking->code)
            ->with('status', 'Booking langsung berhasil dibuat.');
    }

    /**
     * Halaman Tutup atau Buka Slot (GET /admin/tutup-slot).
     */
    public function tutupSlotForm(Request $request)
    {
        $courts = Court::where('is_active', true)->orderBy('sort_order')->get();
        $sessions = PlaySession::where('is_active', true)->orderBy('sort_order')->get();

        $activeBlocks = SlotBlock::with(['court', 'session', 'creator'])
            ->where('play_date', '>=', Carbon::now('Asia/Jayapura')->format('Y-m-d'))
            ->orderBy('play_date')
            ->get();

        $jatahHabisCount = $this->getJatahHabisCount();

        return view('admin.tutup-slot', compact('courts', 'sessions', 'activeBlocks', 'jatahHabisCount'));
    }

    /**
     * Simpan penutupan slot (POST /admin/tutup-slot).
     */
    public function tutupSlotStore(Request $request)
    {
        $validated = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'play_date' => ['required', 'date_format:Y-m-d'],
            'play_session_id' => ['nullable', 'exists:play_sessions,id'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan penutupan slot wajib diisi.',
        ]);

        // Cek apakah ada booking aktif (paid atau pending_payment belum expired) pada slot tersebut
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $slotQuery = BookingSlot::with('booking.user')
            ->where('court_id', $validated['court_id'])
            ->where('play_date', $validated['play_date']);

        if (!empty($validated['play_session_id'])) {
            $slotQuery->where('play_session_id', $validated['play_session_id']);
        }

        $activeBookingSlot = $slotQuery->whereHas('booking', function ($q) use ($nowJayapura) {
            $q->where(function ($sub) use ($nowJayapura) {
                $sub->where('status', BookingStatus::PAID)
                    ->orWhere(function ($p) use ($nowJayapura) {
                        $p->where('status', BookingStatus::PENDING_PAYMENT)
                          ->where('expires_at', '>', $nowJayapura);
                    });
            });
        })->first();

        if ($activeBookingSlot && $activeBookingSlot->booking) {
            $b = $activeBookingSlot->booking;
            $customerName = $b->user ? ($b->user->team_name ?: $b->user->name) : 'Pelanggan';

            throw ValidationException::withMessages([
                'court_id' => "Tidak dapat menutup slot karena ada booking aktif ({$b->code} &middot; {$customerName}) pada jadwal tersebut.",
            ]);
        }

        $block = SlotBlock::create([
            'court_id' => $validated['court_id'],
            'play_date' => $validated['play_date'],
            'play_session_id' => $validated['play_session_id'] ?? null,
            'reason' => $validated['reason'],
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log(
            'slot.closed',
            "Admin menutup slot {$block->court->name} pada {$block->play_date}: {$block->reason}",
            $block,
            [],
            Auth::user()
        );

        return redirect()->route('admin.tutup-slot')
            ->with('status', 'Penutupan slot berhasil disimpan.');
    }

    /**
     * Buka kembali slot yang ditutup (DELETE /admin/tutup-slot/{id}).
     */
    public function bukaSlot(int $id)
    {
        $block = SlotBlock::with('court')->findOrFail($id);

        ActivityLogger::log(
            'slot.opened',
            "Admin membuka kembali slot {$block->court->name} pada {$block->play_date}",
            $block,
            [],
            Auth::user()
        );

        $block->delete();

        return redirect()->route('admin.tutup-slot')
            ->with('status', 'Slot berhasil dibuka kembali.');
    }

    /**
     * Daftar Semua Booking Admin (/admin/booking).
     */
    public function daftarBooking(Request $request)
    {
        $query = Booking::with(['slots.court', 'slots.session', 'user', 'payments'])
            ->latest();

        // Filter rentang tanggal
        if ($request->filled('dari')) {
            $query->whereHas('slots', fn($q) => $q->where('play_date', '>=', $request->dari));
        }
        if ($request->filled('sampai')) {
            $query->whereHas('slots', fn($q) => $q->where('play_date', '<=', $request->sampai));
        }

        // Filter lapangan
        if ($request->filled('court_id')) {
            $query->whereHas('slots', fn($q) => $q->where('court_id', $request->court_id));
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter kategori sewa
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter pencarian teks
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

        return view('admin.booking.index', compact('bookings', 'courts', 'jatahHabisCount'));
    }

    /**
     * Ekspor CSV Daftar Booking Aman dari CSV Injection (GET /admin/booking/ekspor-csv).
     */
    public function eksporCsv(Request $request): StreamedResponse
    {
        $query = Booking::with(['slots.court', 'slots.session', 'user', 'payments'])
            ->latest();

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

        $bookings = $query->get();

        ActivityLogger::log(
            'booking.csv_export',
            'Admin mengekspor data daftar booking ke file CSV',
            null,
            ['count' => $bookings->count()],
            Auth::user()
        );

        $filename = 'daftar-booking-' . Carbon::now('Asia/Jayapura')->format('Ymd-His') . '.csv';

        // Fungsi pembersih CSV Injection: karakter =, +, -, @ diawali tanda petik
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

        return response()->stream(function () use ($bookings, $cleanCell) {
            $handle = fopen('php://output', 'w');

            // Header Kolom CSV
            fputcsv($handle, [
                'Kode Booking',
                'Nama Pemesan',
                'Nomor HP',
                'Nama PB/Tim',
                'Kategori',
                'Status',
                'Total Harga (Rp)',
                'Tanggal Dibuat',
                'Rincian Slot Pertemuan',
            ]);

            foreach ($bookings as $b) {
                $slotsDesc = $b->slots->map(function ($s) {
                    return $s->court->name . ' (' . $s->play_date->format('Y-m-d') . ' ' . $s->session->label . ')';
                })->implode('; ');

                fputcsv($handle, [
                    $cleanCell($b->code),
                    $cleanCell($b->user?->name),
                    $cleanCell($b->user?->phone),
                    $cleanCell($b->user?->team_name),
                    $cleanCell($b->type->label()),
                    $cleanCell($b->status->label()),
                    $cleanCell($b->total_price),
                    $cleanCell($b->created_at->format('Y-m-d H:i:s')),
                    $cleanCell($slotsDesc),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Dashboard Ringkasan & Grafik Okupansi (GET /admin/ringkasan).
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
        $modePantau = false;

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
     * Manajemen Pengguna: Daftar Akun (GET /admin/pengguna).
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
        $modePantau = false;

        return view('admin.pengguna.index', compact('users', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Tambah Pengguna Baru oleh Admin (POST /admin/pengguna).
     */
    public function tambahPengguna(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[0-9\+\-\s]{10,20}$/'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'team_name' => ['nullable', 'string', 'max:100'],
            'role' => ['required', 'in:admin,owner,customer'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $normalizedPhone = normalisasi_nomor_hp($validated['phone']);
        if (User::where('phone', $normalizedPhone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP sudah terdaftar pada akun lain.',
            ]);
        }

        $newUser = User::create([
            'name' => $validated['name'],
            'phone' => $normalizedPhone,
            'email' => $validated['email'] ?? null,
            'team_name' => $validated['team_name'] ?? null,
            'role' => UserRole::from($validated['role']),
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        ActivityLogger::log(
            'user.created',
            "Admin membuat akun baru: {$newUser->name} ({$newUser->phone}) dengan peran {$newUser->role->label()}",
            $newUser,
            ['role' => $newUser->role->value],
            Auth::user()
        );

        return redirect()->route('admin.pengguna.index')
            ->with('status', "Pengguna {$newUser->name} berhasil ditambahkan.");
    }

    /**
     * Detail Pengguna & Riwayat Booking (GET /admin/pengguna/{id}).
     */
    public function showPengguna(int $id)
    {
        $user = User::withCount('bookings')->findOrFail($id);
        $bookings = Booking::with(['slots.court', 'slots.session', 'payments'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(15);

        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = false;

        return view('admin.pengguna.show', compact('user', 'bookings', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Ubah Peran Pengguna (PUT /admin/pengguna/{id}/peran).
     */
    public function ubahPeranPengguna(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => ['required', 'in:admin,owner,customer'],
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Konfirmasi password admin kamu diperlukan.',
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password konfirmasi admin tidak sesuai.',
            ]);
        }

        $newRole = UserRole::from($request->role);

        // Aturan 1: Admin tidak boleh menurunkan dirinya sendiri
        if ($user->id === Auth::id() && $newRole !== UserRole::ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'Kamu tidak boleh menurunkan peran kamu sendiri dari admin.',
            ]);
        }

        // Aturan 2: Harus selalu ada minimal 1 admin aktif di sistem (owner tidak dihitung)
        if ($user->isAdmin() && $newRole !== UserRole::ADMIN) {
            $activeAdminCount = User::where('role', UserRole::ADMIN)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'Harus selalu ada minimal satu admin aktif di sistem.',
                ]);
            }
        }

        $oldRoleLabel = $user->role->label();
        $user->update(['role' => $newRole]);

        ActivityLogger::log(
            'user.role_changed',
            "Admin mengubah peran {$user->name} dari {$oldRoleLabel} menjadi {$newRole->label()}",
            $user,
            ['old_role' => $oldRoleLabel, 'new_role' => $newRole->value],
            Auth::user()
        );

        return redirect()->back()->with('status', "Peran pengguna {$user->name} berhasil diubah menjadi {$newRole->label()}.");
    }

    /**
     * Aktifkan / Nonaktifkan Pengguna (PUT /admin/pengguna/{id}/toggle-status).
     */
    public function toggleStatusPengguna(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Konfirmasi password admin kamu diperlukan.',
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password konfirmasi admin tidak sesuai.',
            ]);
        }

        // Aturan 1: Admin tidak boleh menonaktifkan dirinya sendiri
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'status' => 'Kamu tidak boleh menonaktifkan akun kamu sendiri.',
            ]);
        }

        // Aturan 2: Admin terakhir tidak boleh dinonaktifkan
        if ($user->isAdmin() && $user->is_active) {
            $activeAdminCount = User::where('role', UserRole::ADMIN)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'status' => 'Harus selalu ada minimal satu admin aktif di sistem.',
                ]);
            }
        }

        $user->update(['is_active' => !$user->is_active]);
        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLogger::log(
            'user.status_toggled',
            "Admin telah {$statusText} akun pengguna {$user->name} ({$user->phone})",
            $user,
            ['is_active' => $user->is_active],
            Auth::user()
        );

        return redirect()->back()->with('status', "Akun pengguna {$user->name} berhasil {$statusText}.");
    }

    /**
     * Atur Ulang Password Pengguna oleh Admin (POST /admin/pengguna/{id}/reset-password).
     */
    public function resetPasswordPengguna(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password_admin' => ['required', 'string'],
            'password_baru' => ['nullable', 'string', 'min:8'],
        ], [
            'password_admin.required' => 'Konfirmasi password admin kamu diperlukan.',
            'password_baru.min' => 'Password baru minimal 8 karakter.',
        ]);

        if (!Hash::check($request->password_admin, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password_admin' => 'Password konfirmasi admin tidak sesuai.',
            ]);
        }

        $temporaryPassword = $request->filled('password_baru')
            ? $request->password_baru
            : Str::random(10);

        $user->update([
            'password' => Hash::make($temporaryPassword),
        ]);

        ActivityLogger::log(
            'user.password_reset',
            "Admin mengatur ulang password pengguna {$user->name} ({$user->phone})",
            $user,
            [],
            Auth::user()
        );

        return redirect()->back()->with('status', "Password untuk {$user->name} berhasil diatur ulang menjadi: {$temporaryPassword}");
    }

    /**
     * Halaman Log Aktivitas Sistem (GET /admin/aktivitas).
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
        $modePantau = false;

        return view('admin.aktivitas', compact('logs', 'distinctActions', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Halaman Laporan Operasional & Keuangan (GET /admin/laporan).
     */
    public function laporan(Request $request, DashboardService $dashboardService)
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $dari = $request->get('dari', $nowJayapura->copy()->subDays(30)->toDateString());
        $sampai = $request->get('sampai', $nowJayapura->toDateString());

        $reportData = $dashboardService->getReportData($dari, $sampai, $nowJayapura);
        $jatahHabisCount = $this->getJatahHabisCount();
        $modePantau = false;

        return view('admin.laporan', compact('reportData', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Ekspor CSV Laporan Transaksi Aman dari CSV Injection (GET /admin/laporan/ekspor-csv).
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
            "Ekspor CSV laporan transaksi dari {$dari} s.d. {$sampai}",
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
     * Halaman Pengaturan Operasional (GET /admin/pengaturan).
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
        $modePantau = false;

        return view('admin.pengaturan', compact('settings', 'jatahHabisCount', 'modePantau'));
    }

    /**
     * Simpan Perubahan Pengaturan Operasional (PUT /admin/pengaturan).
     */
    public function updatePengaturan(Request $request)
    {
        $validated = $request->validate([
            'price_single' => ['required', 'integer', 'min:10000', 'max:50000000'],
            'price_package' => ['required', 'integer', 'min:10000', 'max:50000000'],
            'hold_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'max_pending_bookings' => ['required', 'integer', 'min:1', 'max:10'],
            'renew_notice_remaining' => ['required', 'integer', 'min:1', 'max:3'],
            'last_day_notice_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Konfirmasi password admin kamu diperlukan untuk mengubah pengaturan.',
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password konfirmasi admin tidak sesuai.',
            ]);
        }

        $oldValues = [
            'price_single' => Setting::getInt('price_single', 200000),
            'price_package' => Setting::getInt('price_package', 600000),
            'hold_minutes' => Setting::getInt('hold_minutes', 15),
            'max_pending_bookings' => Setting::getInt('max_pending_bookings', 2),
            'renew_notice_remaining' => Setting::getInt('renew_notice_remaining', 1),
            'last_day_notice_hour' => Setting::getInt('last_day_notice_hour', 6),
        ];

        $newValues = [
            'price_single' => (int) $validated['price_single'],
            'price_package' => (int) $validated['price_package'],
            'hold_minutes' => (int) $validated['hold_minutes'],
            'max_pending_bookings' => (int) $validated['max_pending_bookings'],
            'renew_notice_remaining' => (int) $validated['renew_notice_remaining'],
            'last_day_notice_hour' => (int) $validated['last_day_notice_hour'],
        ];

        foreach ($newValues as $key => $val) {
            Setting::set($key, $val);
        }

        ActivityLogger::log(
            'setting.updated',
            'Admin memperbarui pengaturan operasional sistem',
            null,
            ['old' => $oldValues, 'new' => $newValues],
            Auth::user()
        );

        return redirect()->route('admin.pengaturan')
            ->with('status', 'Pengaturan operasional berhasil diperbarui. Nilai baru hanya berlaku untuk booking baru.');
    }
}

