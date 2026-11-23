<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat detail booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Admin, owner (mode pantau), dan pemilik booking berhak melihat
        if ($user->isAdmin() || $user->isOwner()) {
            return true;
        }

        return $booking->user_id === $user->id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat booking baru.
     */
    public function create(User $user): bool
    {
        // Akun owner hanya untuk memantau, tidak boleh membuat booking
        return !$user->isOwner();
    }

    /**
     * Tentukan apakah pengguna dapat membatalkan booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        // Hanya pemilik booking yang boleh membatalkan, dan hanya jika statusnya menunggu bayar
        return $booking->user_id === $user->id
            && $booking->status === BookingStatus::PENDING_PAYMENT;
    }
}
