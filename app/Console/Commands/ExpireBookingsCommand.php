<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ExpireBookingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan booking yang melewati batas waktu hold dan membebaskan slotnya';

    /**
     * Execute the console command.
     */
    public function handle(BookingService $bookingService): int
    {
        $count = $bookingService->cleanupExpiredHolds();

        if ($count > 0) {
            $this->info("Berhasil membersihkan {$count} booking kedaluwarsa dan membebaskan slotnya.");
        } else {
            $this->line('Tidak ada booking yang kedaluwarsa.');
        }

        return Command::SUCCESS;
    }
}
