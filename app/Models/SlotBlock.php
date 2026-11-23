<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotBlock extends Model
{
    use HasFactory;

    protected $table = 'slot_blocks';

    protected $fillable = [
        'court_id',
        'play_date',
        'play_session_id',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'play_date' => 'date:Y-m-d',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'court_id');
    }

    public function playSession(): BelongsTo
    {
        return $this->belongsTo(PlaySession::class, 'play_session_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
