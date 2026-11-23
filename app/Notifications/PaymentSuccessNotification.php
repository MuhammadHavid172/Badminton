<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification
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
            'title' => 'Pembayaran Berhasil',
            'message' => "Pembayaran booking {$this->booking->code} sebesar " . rupiah($this->booking->total_price) . " telah kami terima. Selamat bermain!",
            'booking_code' => $this->booking->code,
            'booking_id' => $this->booking->id,
            'type' => 'payment_success',
        ];
    }
}
