<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed konfigurasi awal ke tabel settings.
     */
    public function run(): void
    {
        $defaults = [
            'price_single' => 200000,
            'price_package' => 600000,
            'hold_minutes' => 15,
            'max_pending_bookings' => 2,
            'renew_notice_remaining' => 1,
            'last_day_notice_hour' => 6,
        ];

        foreach ($defaults as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
