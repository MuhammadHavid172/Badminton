<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman masuk (/masuk).
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.masuk');
    }

    /**
     * Memproses login pengguna.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Nomor HP atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginInput = trim($request->input('login'));
        $throttleKey = Str::lower($loginInput) . '|' . $request->ip();

        // Rate limiting: 5 percobaan per menit per identitas & IP
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput($request->only('login'))->withErrors([
                'login' => "Terlalu banyak percobaan masuk. Silakan tunggu {$seconds} detik lagi.",
            ]);
        }

        // Cek apakah input berupa email atau nomor HP
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', Str::lower($loginInput))->first();
        } else {
            $normalizedPhone = normalisasi_nomor_hp($loginInput);
            $user = User::where('phone', $normalizedPhone)->first();
        }

        // Kredensial tidak cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'Nomor HP/email atau password yang kamu masukkan salah.',
            ]);
        }

        // Cek apakah akun aktif
        if (!$user->is_active) {
            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'Akun kamu sedang dinonaktifkan. Hubungi admin untuk informasi lebih lanjut.',
            ]);
        }

        // Bersihkan throttle
        RateLimiter::clear($throttleKey);

        // Login dan regenerasi sesi
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        ActivityLogger::log(
            'auth.login',
            "Pengguna {$user->name} berhasil masuk",
            $user,
            ['ip' => $request->ip()],
            $user
        );

        return $this->redirectBasedOnRole($user, true);
    }

    /**
     * Menampilkan halaman pendaftaran (/daftar).
     */
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.daftar');
    }

    /**
     * Memproses pendaftaran customer baru.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string',
            'email' => 'nullable|email|max:100',
            'team_name' => 'nullable|string|max:100',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $phone = normalisasi_nomor_hp($request->phone);

        // Validasi format nomor HP Indonesia (10-14 digit diawali 08)
        if (!preg_match('/^08[0-9]{8,12}$/', $phone)) {
            return back()->withInput()->withErrors([
                'phone' => 'Nomor HP tidak valid. Gunakan nomor HP aktif 10-14 digit diawali 08 (contoh: 08123456789).',
            ]);
        }

        // Cek keunikan nomor HP
        if (User::where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors([
                'phone' => 'Nomor HP ini sudah terdaftar. Silakan masuk jika sudah punya akun.',
            ]);
        }

        // Cek keunikan email jika diisi
        $email = $request->email ? Str::lower(trim($request->email)) : null;
        if ($email && User::where('email', $email)->exists()) {
            return back()->withInput()->withErrors([
                'email' => 'Email ini sudah terdaftar pada akun lain.',
            ]);
        }

        $user = User::create([
            'name' => trim($request->name),
            'phone' => $phone,
            'email' => $email,
            'team_name' => $request->team_name ? trim($request->team_name) : null,
            'role' => UserRole::CUSTOMER,
            'is_active' => true,
            'password' => Hash::make($request->password),
        ]);

        ActivityLogger::log(
            'auth.register',
            "Pengguna {$user->name} mendaftar sebagai customer baru",
            $user,
            ['phone' => $phone],
            $user
        );

        // Login otomatis setelah daftar
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('akun'));
    }

    /**
     * Memproses keluar pengguna (POST /keluar).
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            ActivityLogger::log('auth.logout', "Pengguna {$user->name} keluar dari sistem", $user, [], $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda');
    }

    /**
     * Mengarahkan pengguna sesuai perannya (dengan intended URL jika ada).
     */
    protected function redirectBasedOnRole(User $user, bool $useIntended = false): RedirectResponse
    {
        $defaultRoute = match ($user->role) {
            UserRole::ADMIN => route('admin.dashboard'),
            UserRole::OWNER => route('owner.dashboard'),
            default => route('akun'),
        };

        return $useIntended ? redirect()->intended($defaultRoute) : redirect($defaultRoute);
    }
}
