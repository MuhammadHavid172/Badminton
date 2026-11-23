<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    /**
     * Seed daftar lapangan dari config/gor.php.
     */
    public function run(): void
    {
        $courts = config('gor.courts', [
            ['name' => 'Lapangan 1', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Lapangan 2', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'Lapangan 3', 'sort_order' => 3, 'is_active' => true],
        ]);

        foreach ($courts as $courtData) {
            Court::updateOrCreate(
                ['name' => $courtData['name']],
                [
                    'sort_order' => $courtData['sort_order'] ?? 0,
                    'is_active' => $courtData['is_active'] ?? true,
                ]
            );
        }
    }
}
