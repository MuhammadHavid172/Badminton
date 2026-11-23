<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSlot extends Model
{
    use HasFactory;

    protected $table = 'booking_slots';

    protected $fillable = [
        'booking_id',
        'court_id',
        'play_session_id',
        'play_date',
        'checked_in_at',
        'checked_in_by',
        'attendance_note',
    ];

    protected $casts = [
        'play_date' => 'date:Y-m-d',
        'checked_in_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'court_id');
    }

    public function playSession(): BelongsTo
    {
        return $this->belongsTo(PlaySession::class, 'play_session_id');
    }

    public function session(): BelongsTo
    {
        return $this->playSession();
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
