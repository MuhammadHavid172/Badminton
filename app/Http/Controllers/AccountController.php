<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Halaman profil dan akun pengguna (/akun).
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('auth.akun', compact('user'));
    }

    /**
     * Memperbarui informasi profil pengguna.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100|unique:users,email,' . $user->id,
            'team_name' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
        ]);

        $user->update([
            'name' => trim($request->name),
            'email' => $request->email ? strtolower(trim($request->email)) : null,
            'team_name' => $request->team_name ? trim($request->team_name) : null,
        ]);

        ActivityLogger::log('user.profile_update', "Pengguna {$user->name} memperbarui profil", $user, [], $user);

        return back()->with('status_profile', 'Profil kamu berhasil diperbarui.');
    }

    /**
     * Memperbarui password pengguna dengan verifikasi password lama.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama yang kamu masukkan tidak cocok.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLogger::log('user.password_change', "Pengguna {$user->name} mengubah password", $user, [], $user);

        return back()->with('status_password', 'Password kamu berhasil diubah.');
    }
}
