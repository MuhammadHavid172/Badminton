<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PackageLastDayNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Hari Ini Pertemuan Terakhir',
            'message' => "Hari ini adalah pertemuan terakhir untuk sewa Bulanan {$this->booking->code}. Mau lanjut main bulan depan? Perpanjang sekarang ya.",
            'booking_code' => $this->booking->code,
            'booking_id' => $this->booking->id,
            'type' => 'package_last_day',
        ];
    }
}
