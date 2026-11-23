<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Setting;
use App\Models\SlotBlock;
use App\Notifications\PackageEndedNotification;
use App\Notifications\PackageLastDayNotification;
use App\Notifications\PackageRenewalNoticeNotification;
use App\Services\PackageLifecycle;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyRenewalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:notify-renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengirimkan notifikasi pengingat perpanjangan bulanan, hari terakhir, dan berakhirnya masa sewa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nowJayapura = Carbon::now('Asia/Jayapura');
        $noticeHour = Setting::getInt('last_day_notice_hour', 6);
        $renewNoticeRemaining = Setting::getInt('renew_notice_remaining', 1);

        $bookings = Booking::where('type', BookingType::PACKAGE)
            ->where('status', BookingStatus::PAID)
            ->with(['slots.court', 'slots.session', 'user'])
            ->get();

        $notifiedCount = 0;

        foreach ($bookings as $booking) {
            if (!$booking->user) {
                continue;
            }

            $lifecycle = new PackageLifecycle($booking);
            $contStatus = $lifecycle->continuationStatus($nowJayapura);

            // Jangan kirim notifikasi jika sudah diperpanjang (sudah_lanjut)
            if ($contStatus === 'sudah_lanjut') {
                continue;
            }

            $remaining = $lifecycle->remainingMeetings($nowJayapura);
            $state = $lifecycle->state($nowJayapura);

            // 1. Notifikasi Jatah Hampir Habis (idempoten via renewal_notified_at)
            if ($remaining <= $renewNoticeRemaining && $remaining >= 1 && is_null($booking->renewal_notified_at)) {
                $booking->user->notify(new PackageRenewalNoticeNotification($booking, $remaining));
                $booking->update(['renewal_notified_at' => $nowJayapura]);
                $notifiedCount++;
            }

            // 2. Notifikasi Terakhir Hari Ini (idempoten via last_day_notified_at, dikirim sejak last_day_notice_hour)
            if ($state === 'terakhir_hari_ini' && $nowJayapura->hour >= $noticeHour && is_null($booking->last_day_notified_at)) {
                $booking->user->notify(new PackageLastDayNotification($booking));
                $booking->update(['last_day_notified_at' => $nowJayapura]);
                $notifiedCount++;
            }

            // 3. Notifikasi Berakhir (idempoten via ended_notified_at)
            if ($remaining === 0 && is_null($booking->ended_notified_at)) {
                $lastSlot = $lifecycle->lastSlot();
                $isSlotStillVacant = true;

                if ($lastSlot) {
                    $nextDate = Carbon::parse($lastSlot->play_date, 'Asia/Jayapura')->addWeek()->format('Y-m-d');
                    $isTaken = BookingSlot::where('court_id', $lastSlot->court_id)
                        ->where('play_session_id', $lastSlot->play_session_id)
                        ->where('play_date', $nextDate)
                        ->exists()
                        || SlotBlock::where('court_id', $lastSlot->court_id)
                        ->where('play_date', $nextDate)
                        ->exists();

                    $isSlotStillVacant = !$isTaken;
                }

                $booking->user->notify(new PackageEndedNotification($booking, $isSlotStillVacant));
                $booking->update(['ended_notified_at' => $nowJayapura]);
                $notifiedCount++;
            }
        }

        $this->info("Pemeriksaan notifikasi perpanjangan selesai. Total {$notifiedCount} notifikasi terkirim.");

        return Command::SUCCESS;
    }
}
