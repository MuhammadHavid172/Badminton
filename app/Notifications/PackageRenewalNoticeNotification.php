<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PackageRenewalNoticeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public int $remainingMeetings = 1
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Jatah Main Hampir Habis',
            'message' => "Jatah sewa Bulanan {$this->booking->code} tersisa {$this->remainingMeetings} pertemuan lagi. Perpanjang sekarang sebelum slotnya diambil orang lain ya.",
            'booking_code' => $this->booking->code,
            'booking_id' => $this->booking->id,
            'type' => 'package_renewal_notice',
        ];
    }
}
