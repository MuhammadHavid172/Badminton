<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'type',
        'status',
        'total_price',
        'expires_at',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'refund_note',
        'renewed_from_id',
        'renewal_notified_at',
        'last_day_notified_at',
        'ended_notified_at',
        'created_by',
    ];

    protected $casts = [
        'type' => BookingType::class,
        'status' => BookingStatus::class,
        'total_price' => 'integer',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'renewal_notified_at' => 'datetime',
        'last_day_notified_at' => 'datetime',
        'ended_notified_at' => 'datetime',
    ];

    /**
     * Generate kode booking acak 8 karakter unik (huruf kapital dan angka tanpa karakter ambigu).
     */
    public static function generateCode(): string
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'renewed_from_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Booking::class, 'renewed_from_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class, 'booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }

    public function isPending(): bool
    {
        return $this->status === BookingStatus::PENDING_PAYMENT;
    }

    public function isPaid(): bool
    {
        return $this->status === BookingStatus::PAID;
    }

    public function isExpired(): bool
    {
        if ($this->status === BookingStatus::EXPIRED) {
            return true;
        }

        if ($this->isPending() && $this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }
}
