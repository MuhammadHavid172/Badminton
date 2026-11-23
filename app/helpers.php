<?php

use Carbon\Carbon;

if (!function_exists('rupiah')) {
    /**
     * Format angka menjadi format Rupiah ("Rp 200.000").
     *
     * @param int|float|string|null $angka
     * @return string
     */
    function rupiah($angka): string
    {
        $nilai = (int) round((float) ($angka ?? 0));
        return 'Rp ' . number_format($nilai, 0, ',', '.');
    }
}

if (!function_exists('tanggal_indonesia')) {
    /**
     * Format tanggal ke bahasa Indonesia dengan zona waktu Asia/Jayapura (WIT).
     *
     * @param \DateTimeInterface|string|null $date
     * @param bool $withDay
     * @param bool $withTime
     * @return string
     */
    function tanggal_indonesia($date, bool $withDay = true, bool $withTime = false): string
    {
        if (empty($date)) {
            return '-';
        }

        $carbon = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $carbon->setTimezone('Asia/Jayapura');

        $hari = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $dayName = $hari[$carbon->dayOfWeek];
        $monthName = $bulan[$carbon->month];
        $dayNum = $carbon->day;
        $year = $carbon->year;

        $result = $withDay
            ? "{$dayName}, {$dayNum} {$monthName} {$year}"
            : "{$dayNum} {$monthName} {$year}";

        if ($withTime) {
            $result .= ' ' . $carbon->format('H.i') . ' WIT';
        }

        return $result;
    }
}

if (!function_exists('normalisasi_nomor_hp')) {
    /**
     * Normalisasi nomor HP ke format 08xxxxxxxxxx:
     * - Menghapus spasi, tanda hubung, titik, dan tanda kurung.
     * - Mengubah awalan +62 atau 62 menjadi 0.
     * - Menambahkan 0 jika diawali angka 8.
     *
     * @param string|null $phone
     * @return string
     */
    function normalisasi_nomor_hp(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Ambil hanya digit angka
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($clean, '62')) {
            $clean = '0' . substr($clean, 2);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '0' . $clean;
        }

        return $clean;
    }
}

if (!function_exists('normalize_phone_number')) {
    /**
     * Alias normalisasi_nomor_hp.
     */
    function normalize_phone_number(?string $phone): string
    {
        return normalisasi_nomor_hp($phone);
    }
}
