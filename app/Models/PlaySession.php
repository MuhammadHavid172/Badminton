<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlaySession extends Model
{
    use HasFactory;

    protected $table = 'play_sessions';

    protected $fillable = [
        'label',
        'start_time',
        'end_time',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke booking slots pada sesi ini.
     */
    public function bookingSlots(): HasMany
    {
        return $this->hasMany(BookingSlot::class, 'play_session_id');
    }

    /**
     * Relasi ke blokir slot pada sesi ini.
     */
    public function slotBlocks(): HasMany
    {
        return $this->hasMany(SlotBlock::class, 'play_session_id');
    }

    /**
     * Format rentang jam bermain: "08.00-11.00".
     */
    public function formattedRange(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);
        return str_replace(':', '.', "{$start}-{$end}");
    }
}
