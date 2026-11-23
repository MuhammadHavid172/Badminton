<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $reason,
        public ?string $refundNote = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Booking {$this->booking->code} telah dibatalkan oleh pengelola GOR dengan alasan: {$this->reason}.";
        if ($this->refundNote) {
            $message .= " Catatan pengembalian dana: {$this->refundNote}";
        }

        return [
            'title' => 'Booking Dibatalkan oleh Admin',
            'message' => $message,
            'booking_code' => $this->booking->code,
            'booking_id' => $this->booking->id,
            'type' => 'booking_cancelled_admin',
        ];
    }
}
