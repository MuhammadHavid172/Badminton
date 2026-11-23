<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    /**
     * Uji fungsi helper rupiah().
     */
    public function test_rupiah_formatter(): void
    {
        $this->assertEquals('Rp 200.000', rupiah(200000));
        $this->assertEquals('Rp 600.000', rupiah(600000));
        $this->assertEquals('Rp 0', rupiah(0));
        $this->assertEquals('Rp 0', rupiah(null));
        $this->assertEquals('Rp 150.000', rupiah('150000'));
        $this->assertEquals('Rp 50.500', rupiah(50500.25));
    }

    /**
     * Uji fungsi helper tanggal_indonesia() dengan zona Asia/Jayapura (WIT).
     */
    public function test_tanggal_indonesia_formatter(): void
    {
        // 2026-11-23 adalah hari Senin
        $date = Carbon::parse('2026-11-23 14:30:00', 'Asia/Jayapura');

        // Dengan nama hari
        $this->assertEquals('Senin, 23 November 2026', tanggal_indonesia($date, true, false));

        // Tanpa nama hari
        $this->assertEquals('23 November 2026', tanggal_indonesia($date, false, false));

        // Dengan waktu WIT
        $this->assertEquals('Senin, 23 November 2026 14.30 WIT', tanggal_indonesia($date, true, true));

        // Tanggal null / kosong
        $this->assertEquals('-', tanggal_indonesia(null));
    }
}
