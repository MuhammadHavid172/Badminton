<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingExpiredNotification extends Notification
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
            'title' => 'Waktu Pembayaran Habis',
            'message' => "Booking {$this->booking->code} telah kedaluwarsa karena melewati batas waktu tahan pembayaran. Slot telah dibebaskan kembali.",
            'booking_code' => $this->booking->code,
            'booking_id' => $this->booking->id,
            'type' => 'booking_expired',
        ];
    }
}
