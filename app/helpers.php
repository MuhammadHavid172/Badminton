<?php

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
