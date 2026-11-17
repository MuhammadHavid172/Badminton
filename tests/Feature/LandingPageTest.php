<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    /**
     * Memastikan route beranda "/" mengembalikan status 200.
     */
    public function test_beranda_mengembalikan_status_200(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Memastikan beranda memuat kalimat raksasa hero: "Saatnya", "Turun", "Lapangan.".
     */
    public function test_beranda_memuat_tiga_kata_hero(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Saatnya');
        $response->assertSee('Turun');
        $response->assertSee('Lapangan');
    }

    /**
     * Memastikan route halaman sementara /jadwal mengembalikan 200.
     */
    public function test_halaman_jadwal_mengembalikan_status_200(): void
    {
        $response = $this->get('/jadwal');
        $response->assertStatus(200);
        $response->assertSee('Jadwal Lapangan');
        $response->assertSee('Kembali ke Beranda');
    }

    /**
     * Memastikan route halaman sementara /masuk mengembalikan 200.
     */
    public function test_halaman_masuk_mengembalikan_status_200(): void
    {
        $response = $this->get('/masuk');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun');
        $response->assertSee('Kembali ke Beranda');
    }

    /**
     * Memastikan route halaman sementara /daftar mengembalikan 200.
     */
    public function test_halaman_daftar_mengembalikan_status_200(): void
    {
        $response = $this->get('/daftar');
        $response->assertStatus(200);
        $response->assertSee('Daftar Akun');
        $response->assertSee('Kembali ke Beranda');
    }

    /**
     * Memastikan route /dev/komponen mengembalikan 404 pada environment non-lokal (misal testing / production).
     */
    public function test_dev_komponen_404_di_non_lokal(): void
    {
        // Secara default PHPUnit berjalan pada APP_ENV=testing
        $response = $this->get('/dev/komponen');
        $response->assertStatus(404);
    }

    /**
     * Memastikan route /dev/komponen mengembalikan 200 pada environment local.
     */
    public function test_dev_komponen_200_di_local(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $response = $this->get('/dev/komponen');
        $response->assertStatus(200);
        $response->assertSee('Sistem Desain &amp; Komponen', false);
    }

    /**
     * Memastikan komponen <x-foto> merender wadah fallback saat file tidak ada.
     */
    public function test_komponen_foto_memakai_fallback_saat_file_tidak_ada(): void
    {
        $rendered = Blade::render('<x-foto nama="file-tidak-pernah-ada-12345.jpg" alt="Foto Contoh" :width="800" :height="600" />');
        
        $this->assertStringContainsString('foto-fallback', $rendered);
        $this->assertStringContainsString('role="img"', $rendered);
        $this->assertStringNotContainsString('<img', $rendered);
    }

    /**
     * Memastikan helper rupiah memformat angka dengan benar.
     */
    public function test_helper_rupiah_memformat_dengan_benar(): void
    {
        $this->assertEquals('Rp 200.000', rupiah(200000));
        $this->assertEquals('Rp 600.000', rupiah(600000));
        $this->assertEquals('Rp 0', rupiah(0));
    }
}
