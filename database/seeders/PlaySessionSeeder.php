<?php

namespace Database\Seeders;

use App\Models\PlaySession;
use Illuminate\Database\Seeder;

class PlaySessionSeeder extends Seeder
{
    /**
     * Seed daftar sesi bermain dari config/gor.php.
     */
    public function run(): void
    {
        $sessions = config('gor.sessions', [
            ['label' => 'Sesi 1', 'start_time' => '08:00', 'end_time' => '11:00', 'sort_order' => 1, 'is_active' => true],
            ['label' => 'Sesi 2', 'start_time' => '11:00', 'end_time' => '15:00', 'sort_order' => 2, 'is_active' => true],
            ['label' => 'Sesi 3', 'start_time' => '15:00', 'end_time' => '19:00', 'sort_order' => 3, 'is_active' => true],
            ['label' => 'Sesi 4', 'start_time' => '19:00', 'end_time' => '23:00', 'sort_order' => 4, 'is_active' => true],
        ]);

        foreach ($sessions as $sessionData) {
            PlaySession::updateOrCreate(
                ['label' => $sessionData['label']],
                [
                    'start_time' => $sessionData['start_time'],
                    'end_time' => $sessionData['end_time'],
                    'sort_order' => $sessionData['sort_order'] ?? 0,
                    'is_active' => $sessionData['is_active'] ?? true,
                ]
            );
        }
    }
}
