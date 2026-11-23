<?php

namespace App\Contracts;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;

interface PaymentGateway
{
    /**
     * Memproses dan mengonfirmasi pembayaran berhasil secara aman & idempoten.
     */
    public function markAsPaid(Booking $booking, User $actor, array $payload = []): Payment;

    /**
     * Mencatat percobaan pembayaran yang gagal.
     */
    public function markAsFailed(Booking $booking, User $actor, string $reason = 'Simulasi pembayaran gagal'): Payment;
}
