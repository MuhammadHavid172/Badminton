<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';

    /**
     * Label teks status pembayaran untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu',
            self::PAID => 'Berhasil',
            self::FAILED => 'Gagal',
        };
    }
}
