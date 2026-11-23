<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAccountCommand extends Command
{
    /**
     * Nama dan tanda tangan command.
     */
    protected $signature = 'gor:buat-akun 
                            {--nama= : Nama lengkap pengguna}
                            {--hp= : Nomor handphone aktif}
                            {--email= : Alamat email (opsional)}
                            {--password= : Password akun (minimal 8 karakter)}
                            {--peran= : Peran akun (admin atau owner)}';

    /**
     * Alias command.
     */
    protected $aliases = ['gor:buat-admin'];

    /**
     * Deskripsi command.
     */
    protected $description = 'Membuat akun pengelola GOR (Admin atau Owner)';

    /**
     * Eksekusi perintah command.
     */
    public function handle(): int
    {
        $this->info('=== PEMBUATAN AKUN PENGELOLA GOR ===');

        // 1. Tentukan Nama
        $nama = $this->option('nama') ?: $this->ask('Nama lengkap');
        while (empty(trim((string) $nama))) {
            $this->error('Nama lengkap tidak boleh kosong.');
            $nama = $this->ask('Nama lengkap');
        }

        // 2. Tentukan Nomor HP
        $hpInput = $this->option('hp') ?: $this->ask('Nomor HP (contoh: 08123456789)');
        $phone = normalisasi_nomor_hp($hpInput);

        while (!preg_match('/^08[0-9]{8,12}$/', $phone) || User::where('phone', $phone)->exists()) {
            if (!preg_match('/^08[0-9]{8,12}$/', $phone)) {
                $this->error('Nomor HP tidak valid. Gunakan format 10-14 digit diawali 08.');
            } else {
                $this->error("Nomor HP '{$phone}' sudah terdaftar.");
            }
            $hpInput = $this->ask('Nomor HP');
            $phone = normalisasi_nomor_hp($hpInput);
        }

        // 3. Tentukan Email (Opsional)
        $email = $this->option('email');
        if ($email === null && $this->input->isInteractive() && empty($this->option('nama'))) {
            $email = $this->ask('Email (opsional, tekan Enter untuk lewati)');
        }
        $email = !empty(trim((string) $email)) ? Str::lower(trim((string) $email)) : null;

        if ($email && User::where('email', $email)->exists()) {
            $this->error("Email '{$email}' sudah terdaftar. Mengabaikan email...");
            $email = null;
        }

        // 4. Tentukan Password
        $password = $this->option('password');
        if (empty($password)) {
            $password = $this->secret('Password (minimal 8 karakter)');
        }
        while (strlen((string) $password) < 8) {
            $this->error('Password harus minimal 8 karakter.');
            $password = $this->secret('Password (minimal 8 karakter)');
        }

        // 5. Tentukan Peran
        $peranInput = $this->option('peran');
        // Jika dipanggil via alias gor:buat-admin dan opsi peran kosong, bawaan adalah admin
        if (empty($peranInput) && str_ends_with($this->getName() ?? '', 'buat-admin')) {
            $peranInput = 'admin';
        }

        if (empty($peranInput) || !in_array(strtolower($peranInput), ['admin', 'owner'])) {
            if ($this->input->isInteractive() && empty($this->option('nama'))) {
                $peranInput = $this->choice('Pilih peran akun', ['admin', 'owner'], 0);
            } else {
                $peranInput = 'admin';
            }
        }
        $role = strtolower($peranInput) === 'owner' ? UserRole::OWNER : UserRole::ADMIN;

        // 6. Simpan User
        $user = User::create([
            'name' => trim((string) $nama),
            'phone' => $phone,
            'email' => $email,
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make((string) $password),
        ]);

        ActivityLogger::log(
            'admin.account_created',
            "Akun {$role->label()} '{$user->name}' ({$user->phone}) dibuat via CLI artisan",
            $user
        );

        $this->newLine();
        $this->info("Berhasil! Akun {$role->label()} '{$user->name}' ({$user->phone}) telah dibuat.");

        return Command::SUCCESS;
    }
}
