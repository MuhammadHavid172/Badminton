<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke seluruh booking slots di lapangan ini.
     */
    public function bookingSlots(): HasMany
    {
        return $this->hasMany(BookingSlot::class, 'court_id');
    }

    /**
     * Relasi ke blokir penutupan lapangan.
     */
    public function slotBlocks(): HasMany
    {
        return $this->hasMany(SlotBlock::class, 'court_id');
    }
}
