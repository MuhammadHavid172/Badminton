<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    /**
     * Nama dan tanda tangan command alias.
     */
    protected $signature = 'gor:buat-admin
                            {--nama= : Nama lengkap admin}
                            {--hp= : Nomor handphone aktif}
                            {--email= : Alamat email (opsional)}
                            {--password= : Password akun (minimal 8 karakter)}';

    /**
     * Deskripsi command alias.
     */
    protected $description = 'Alias cepat untuk membuat akun Admin GOR (gor:buat-akun --peran=admin)';

    /**
     * Jalankan perintah dengan memanggil command gor:buat-akun.
     */
    public function handle(): int
    {
        return $this->call('gor:buat-akun', [
            '--nama' => $this->option('nama'),
            '--hp' => $this->option('hp'),
            '--email' => $this->option('email'),
            '--password' => $this->option('password'),
            '--peran' => 'admin',
        ]);
    }
}
