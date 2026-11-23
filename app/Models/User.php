<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Nilai bawaan atribut model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'role' => UserRole::CUSTOMER,
    ];

    /**
     * Atribut yang dapat diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'role',
        'phone',
        'email',
        'team_name',
        'is_active',
        'password',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atribut casting tipe data.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => UserRole::class,
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Relasi ke seluruh booking milik user ini.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    /**
     * Relasi ke booking yang dibuat atas nama orang lain oleh user ini (misal oleh admin).
     */
    public function createdBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    /**
     * Relasi ke log aktivitas pengguna.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /**
     * Cek apakah user memiliki peran admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Cek apakah user memiliki peran owner (hanya lihat).
     */
    public function isOwner(): bool
    {
        return $this->role === UserRole::OWNER;
    }

    /**
     * Cek apakah user adalah customer biasa.
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }
}
