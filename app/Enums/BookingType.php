<?php

namespace App\Enums;

enum BookingType: string
{
    case SINGLE = 'single';
    case PACKAGE = 'package';

    /**
     * Label resmi kategori sewa di seluruh antarmuka sistem.
     * WAJIB: Selalu gunakan "Harian" dan "Bulanan" (jangan "Sekali main" atau "Paket bulanan").
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Harian',
            self::PACKAGE => 'Bulanan',
        };
    }
}
