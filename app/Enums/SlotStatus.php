<?php

namespace App\Enums;

enum SlotStatus: string
{
    case KOSONG = 'kosong';
    case MENUNGGU_BAYAR = 'menunggu_bayar';
    case TERISI = 'terisi';
    case DITUTUP = 'ditutup';
    case LEWAT = 'lewat';

    /**
     * Label teks status slot jadwal.
     */
    public function label(): string
    {
        return match ($this) {
            self::KOSONG => 'Kosong',
            self::MENUNGGU_BAYAR => 'Menunggu Bayar',
            self::TERISI => 'Terisi',
            self::DITUTUP => 'Ditutup',
            self::LEWAT => 'Lewat',
        };
    }
}
