<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Mencatat aktivitas ke tabel activity_logs.
     *
     * @param string $action Nama aksi terstruktur (misal: 'booking.created', 'slot.blocked')
     * @param string $description Penjelasan ringkas aktivitas dalam bahasa Indonesia
     * @param Model|null $subject Model yang menjadi objek aktivitas
     * @param array $meta Data konteks tambahan dalam bentuk array JSON
     * @param User|null $user Pengguna yang melakukan aksi (opsional, bawaan: auth user)
     * @return ActivityLog
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $meta = [],
        ?User $user = null
    ): ActivityLog {
        /** @var User|null $actor */
        $actor = $user ?? Auth::user();

        $roleValue = null;
        if ($actor) {
            $roleValue = is_object($actor->role) ? $actor->role->value : (string) $actor->role;
        }

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'role' => $roleValue,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'meta' => empty($meta) ? null : $meta,
            'created_at' => now(),
        ]);
    }
}
