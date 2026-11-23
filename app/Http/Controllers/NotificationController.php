<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Tampilan daftar notifikasi pengguna (GET /notifikasi).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(20);

        return view('notifikasi.index', compact('notifications'));
    }

    /**
     * Tandai semua notifikasi telah dibaca (POST /notifikasi/baca-semua).
     */
    public function markAllAsRead(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->route('notifikasi.index')
            ->with('status', 'Seluruh notifikasi telah ditandai dibaca.');
    }

    /**
     * Tandai satu notifikasi telah dibaca dan arahkan ke detail terkait jika ada.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $bookingCode = $notification->data['booking_code'] ?? null;
        if ($bookingCode) {
            return redirect()->route('booking.detail', $bookingCode);
        }

        return redirect()->route('notifikasi.index');
    }
}
