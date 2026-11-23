<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Court;
use App\Models\PlaySession;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PackageLifecycle;
use App\Services\RenewalAlternatives;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Tampilan form booking baru (GET /booking/baru).
     */
    public function create(Request $request)
    {
        // Validasi parameter URL
        $validated = $request->validate([
            'lapangan' => ['required', 'integer', 'exists:courts,id'],
            'sesi' => ['required', 'integer', 'exists:play_sessions,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
        ]);

        // Pengguna owner hanya untuk memantau
        if (Auth::check() && Auth::user()->isOwner()) {
            return response()->view('booking.owner-notice', [], 403);
        }

        $court = Court::findOrFail($validated['lapangan']);
        $session = PlaySession::findOrFail($validated['sesi']);
        $startDate = Carbon::parse($validated['tanggal'], 'Asia/Jayapura')->startOfDay();

        $availability = $this->bookingService->checkSlotAvailability($court, $session, $startDate);

        return view('booking.baru', compact('court', 'session', 'startDate', 'availability'));
    }

    /**
     * Proses pembuatan booking baru (POST /booking).
     */
    public function store(Request $request)
    {
        // Pengguna owner dilarang booking
        if (Auth::check() && Auth::user()->isOwner()) {
            return response()->view('booking.owner-notice', [], 403);
        }

        // 1. Tangani tamu yang mendaftar secara inline
        if (!Auth::check()) {
            $regValidated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'regex:/^[0-9\+\-\s]{10,16}$/'],
                'password' => ['required', 'string', 'min:8'],
                'team_name' => ['nullable', 'string', 'max:100'],
                'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            ], [
                'name.required' => 'Nama lengkap wajib diisi.',
                'phone.required' => 'Nomor HP wajib diisi.',
                'phone.regex' => 'Format nomor HP tidak valid (10-14 digit).',
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal terdiri dari 8 karakter.',
                'email.email' => 'Format alamat email tidak valid.',
                'email.unique' => 'Alamat email ini sudah terdaftar.',
            ]);

            $normalizedPhone = normalisasi_nomor_hp($regValidated['phone']);

            if (User::where('phone', $normalizedPhone)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'Nomor HP ini sudah terdaftar. Silakan masuk terlebih dahulu ya.',
                ]);
            }

            $user = User::create([
                'name' => $regValidated['name'],
                'phone' => $normalizedPhone,
                'email' => $regValidated['email'] ?? null,
                'team_name' => $regValidated['team_name'] ?? null,
                'password' => Hash::make($regValidated['password']),
                'role' => UserRole::CUSTOMER,
                'is_active' => true,
            ]);

            Auth::login($user);
        } else {
            $user = Auth::user();
        }

        // 2. Validasi input booking
        $validated = $request->validate([
            'lapangan' => ['required', 'integer', 'exists:courts,id'],
            'sesi' => ['required', 'integer', 'exists:play_sessions,id'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', 'in:single,package'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'type.required' => 'Pilih kategori sewa (Harian atau Bulanan).',
            'type.in' => 'Kategori sewa tidak valid.',
        ]);

        $court = Court::findOrFail($validated['lapangan']);
        $session = PlaySession::findOrFail($validated['sesi']);
        $type = BookingType::from($validated['type']);

        $booking = $this->bookingService->createBooking(
            $user,
            $court,
            $session,
            $validated['tanggal'],
            $type,
            $validated['notes'] ?? null
        );

        return redirect()->route('booking.bayar', $booking->code)
            ->with('status', 'Booking berhasil dibuat! Selesaikan pembayaran sebelum waktu habis ya.');
    }

    /**
     * Tampilan detail booking (GET /booking/{code}).
     */
    public function show(string $code)
    {
        $this->bookingService->cleanupExpiredHolds();

        $booking = Booking::with(['slots.court', 'slots.session', 'user', 'payments'])
            ->where('code', $code)
            ->firstOrFail();

        $this->authorize('view', $booking);

        return view('booking.detail', compact('booking'));
    }

    /**
     * Halaman pembayaran booking (GET /booking/{code}/bayar).
     */
    public function pay(string $code)
    {
        $this->bookingService->cleanupExpiredHolds();

        $booking = Booking::with(['slots.court', 'slots.session', 'user'])
            ->where('code', $code)
            ->firstOrFail();

        // Hanya pemilik yang dapat mengakses halaman bayar
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $nowJayapura = Carbon::now('Asia/Jayapura');
        if ($booking->status !== BookingStatus::PENDING_PAYMENT || ($booking->expires_at && $booking->expires_at->lessThanOrEqualTo($nowJayapura))) {
            return redirect()->route('booking.detail', $booking->code)
                ->with('error', 'Booking ini tidak dalam status menunggu pembayaran atau batas waktu tahan pembayaran telah habis.');
        }

        return view('booking.bayar', compact('booking'));
    }

    /**
     * Memproses pembayaran simulasi berhasil (POST /booking/{code}/bayar/proses).
     */
    public function processPayment(Request $request, string $code, PaymentGateway $gateway)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $gateway->markAsPaid($booking, Auth::user());

            return redirect()->route('booking.detail', $booking->code)
                ->with('status', 'Pembayaran berhasil dikonfirmasi! Selamat bermain.');
        } catch (ValidationException $e) {
            return redirect()->route('booking.detail', $booking->code)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Memproses simulasi pembayaran gagal (POST /booking/{code}/bayar/gagal).
     */
    public function failPayment(Request $request, string $code, PaymentGateway $gateway)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $gateway->markAsFailed($booking, Auth::user(), 'Simulasi pembayaran gagal atau ditolak.');

        return redirect()->route('booking.bayar', $booking->code)
            ->with('error', 'Pembayaran simulasi ditolak/gagal. Silakan coba lagi.');
    }

    /**
     * Batalkan booking pending payment (POST /booking/{code}/batal).
     */
    public function cancel(Request $request, string $code)
    {
        $booking = Booking::where('code', $code)->firstOrFail();

        $this->authorize('cancel', $booking);

        $this->bookingService->cancelPendingBooking($booking, Auth::user());

        return redirect()->route('booking.detail', $booking->code)
            ->with('status', 'Booking berhasil dibatalkan.');
    }

    /**
     * Memproses perpanjangan sewa Bulanan (POST /booking/{code}/perpanjang).
     */
    public function perpanjang(Request $request, string $code, RenewalAlternatives $renewalAlternatives)
    {
        $originalBooking = Booking::with(['slots.court', 'slots.session', 'user'])
            ->where('code', $code)
            ->firstOrFail();

        if ($originalBooking->user_id !== Auth::id()) {
            abort(403);
        }

        if ($originalBooking->type !== BookingType::PACKAGE || $originalBooking->status !== BookingStatus::PAID) {
            return redirect()->route('booking.detail', $originalBooking->code)
                ->with('error', 'Hanya sewa Bulanan yang lunas yang dapat diperpanjang.');
        }

        $nowJayapura = Carbon::now('Asia/Jayapura');
        $lifecycle = new PackageLifecycle($originalBooking);
        $remaining = $lifecycle->remainingMeetings($nowJayapura);
        $noticeThreshold = Setting::getInt('renew_notice_remaining', 1);

        if ($remaining > $noticeThreshold || $remaining < 1) {
            return redirect()->route('booking.detail', $originalBooking->code)
                ->with('error', "Perpanjangan hanya dapat dilakukan saat jatah tersisa {$noticeThreshold} pertemuan lagi.");
        }

        // Cek apakah sudah pernah diperpanjang
        $existingRenewal = Booking::where('renewed_from_id', $originalBooking->id)
            ->whereIn('status', [BookingStatus::PENDING_PAYMENT, BookingStatus::PAID])
            ->first();

        if ($existingRenewal) {
            return redirect()->route('booking.detail', $existingRenewal->code)
                ->with('status', 'Booking perpanjangan untuk sewa Bulanan ini sudah dibuat sebelumnya.');
        }

        // Cek ketersediaan dan alternatif
        $alternatives = $renewalAlternatives->findAlternatives($originalBooking, $nowJayapura);

        $court = $alternatives['court'];
        $session = $alternatives['session'];

        // Jika pengguna memilih alternatif lapangan/sesi lain dari form
        if ($request->filled('alternative_court_id')) {
            $court = Court::findOrFail($request->alternative_court_id);
        } elseif ($request->filled('alternative_session_id')) {
            $session = PlaySession::findOrFail($request->alternative_session_id);
        } elseif (!$alternatives['available']) {
            // Jika bentrok dan belum memilih alternatif, tampilkan halaman pilihan
            return view('booking.perpanjang-alternatif', [
                'booking' => $originalBooking,
                'alternatives' => $alternatives,
            ]);
        }

        $startDate = $alternatives['target_dates'][0]['date'];
        $newBooking = $this->bookingService->createBooking(
            Auth::user(),
            $court,
            $session,
            $startDate,
            BookingType::PACKAGE,
            "Perpanjangan sewa Bulanan dari {$originalBooking->code}"
        );

        $newBooking->update(['renewed_from_id' => $originalBooking->id]);

        return redirect()->route('booking.bayar', $newBooking->code)
            ->with('status', 'Booking perpanjangan berhasil dibuat! Selesaikan pembayaran sebelum waktu habis.');
    }

    /**
     * Simulasi pembayaran lunas (POST /booking/{code}/simulasi-lunas) - Kompatibilitas backwards local.
     */
    public function simulasiLunas(Request $request, string $code)
    {
        if (!app()->environment('local')) {
            abort(403, 'Aksi ini hanya tersedia di lingkungan pengujian lokal.');
        }

        $booking = Booking::where('code', $code)->firstOrFail();

        $this->authorize('view', $booking);

        $this->bookingService->simulatePaymentSuccess($booking, Auth::user());

        return redirect()->route('booking.detail', $booking->code)
            ->with('status', 'Pembayaran simulasi berhasil. Booking lunas!');
    }

    /**
     * Daftar booking milik pengguna (GET /booking).
     */
    public function myBookings(Request $request)
    {
        $this->bookingService->cleanupExpiredHolds();

        $nowJayapura = Carbon::now('Asia/Jayapura');
        $user = Auth::user();

        $allBookings = Booking::with(['slots.court', 'slots.session', 'payments'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $menungguBayar = collect();
        $akanDatang = collect();
        $riwayat = collect();

        foreach ($allBookings as $booking) {
            if ($booking->status === BookingStatus::PENDING_PAYMENT) {
                if ($booking->expires_at && $booking->expires_at->gt($nowJayapura)) {
                    $menungguBayar->push($booking);
                } else {
                    $riwayat->push($booking);
                }
            } elseif ($booking->status === BookingStatus::PAID) {
                // Cek apakah masih ada slot yang belum selesai di zona Jayapura
                $hasUpcoming = $booking->slots->contains(function ($slot) use ($nowJayapura) {
                    $sessionEndTime = Carbon::parse("{$slot->play_date} {$slot->session->end_time}", 'Asia/Jayapura');
                    return $sessionEndTime->gt($nowJayapura);
                });

                if ($hasUpcoming) {
                    $akanDatang->push($booking);
                } else {
                    $riwayat->push($booking);
                }
            } else {
                // Cancelled atau Expired
                $riwayat->push($booking);
            }
        }

        return view('booking.saya', compact('menungguBayar', 'akanDatang', 'riwayat'));
    }
}
