<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PackageEndedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public bool $isSlotStillVacant = true
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = $this->isSlotStillVacant
            ? "Masa sewa Bulanan {$this->booking->code} telah selesai. Slot untuk minggu depan masih kosong! Pesan kembali sekarang."
            : "Masa sewa Bulanan {$this->booking->code} telah selesai dan slot berikutnya telah dipesan orang lain. Cek jadwal untuk slot lainnya ya.";

        return [
            'title' => 'Masa Sewa Bulanan Berakhir',
            'message' => $message,
            'booking_code' => $this->booking->code,
            'booking_id' => $this->booking->id,
            'type' => 'package_ended',
        ];
    }
}
