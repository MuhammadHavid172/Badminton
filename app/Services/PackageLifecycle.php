<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PackageLifecycle
{
    protected Booking $booking;

    /**
     * Cache opsional untuk renewals guna menghindari query berulang (N+1).
     */
    protected ?Collection $cachedRenewals = null;

    public function __construct(Booking $booking, ?Collection $cachedRenewals = null)
    {
        $this->booking = $booking;
        $this->cachedRenewals = $cachedRenewals;
    }

    /**
     * Memastikan booking adalah paket Bulanan yang sah dan berstatus paid.
     */
    public function isValidPackage(): bool
    {
        return $this->booking->type === BookingType::PACKAGE && $this->booking->status === BookingStatus::PAID;
    }

    /**
     * Mengambil seluruh slot pertemuan terurut berdasarkan tanggal dan jam sesi.
     *
     * @return Collection<int, BookingSlot>
     */
    public function slots(): Collection
    {
        return $this->booking->slots
            ->sortBy(function (BookingSlot $slot) {
                $session = $slot->relationLoaded('session')
                    ? $slot->session
                    : ($slot->relationLoaded('playSession') ? $slot->playSession : ($slot->session ?? null));
                $sessionStart = $session ? $session->start_time : '00:00:00';
                $dateStr = $slot->play_date instanceof Carbon ? $slot->play_date->format('Y-m-d') : (string) $slot->play_date;
                return "{$dateStr} {$sessionStart}";
            })
            ->values();
    }

    /**
     * Total pertemuan paket Bulanan.
     */
    public function totalMeetings(): int
    {
        return $this->slots()->count();
    }

    /**
     * Slot pertemuan terakhir dalam rangkaian paket.
     */
    public function lastSlot(): ?BookingSlot
    {
        return $this->slots()->last();
    }

    /**
     * Tanggal pertemuan terakhir dalam format Y-m-d.
     */
    public function lastPlayDate(): ?string
    {
        $last = $this->lastSlot();
        if (!$last) {
            return null;
        }

        return $last->play_date instanceof Carbon
            ? $last->play_date->format('Y-m-d')
            : (string) $last->play_date;
    }

    /**
     * Menghitung nomor pertemuan (1 s/d total) untuk slot atau tanggal tertentu.
     */
    public function meetingNumberForDate(string $date): ?int
    {
        foreach ($this->slots() as $index => $slot) {
            $slotDate = $slot->play_date instanceof Carbon ? $slot->play_date->format('Y-m-d') : (string) $slot->play_date;
            if ($slotDate === $date) {
                return $index + 1;
            }
        }

        return null;
    }

    /**
     * Menghitung nomor pertemuan (1 s/d total) untuk objek BookingSlot tertentu.
     */
    public function meetingNumberForSlot(BookingSlot $slot): ?int
    {
        foreach ($this->slots() as $index => $s) {
            if ($s->id === $slot->id) {
                return $index + 1;
            }
        }

        $date = $slot->play_date instanceof Carbon ? $slot->play_date->format('Y-m-d') : (string) $slot->play_date;
        return $this->meetingNumberForDate($date);
    }

    /**
     * Memeriksa apakah pertemuan terakhir paket berlangsung hari ini (Jayapura).
     */
    public function isLastMeetingToday(?Carbon $now = null): bool
    {
        $currentTime = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $lastPlayDate = $this->lastPlayDate();

        return $lastPlayDate === $currentTime->format('Y-m-d');
    }

    /**
     * Menghitung sisa pertemuan yang belum selesai menurut zona waktu Asia/Jayapura.
     * Sesi dianggap selesai jika jam akhir sesi pada tanggal tersebut sudah lewat dari waktu saat ini.
     */
    public function remainingMeetings(?Carbon $now = null): int
    {
        $currentTime = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $remaining = 0;

        foreach ($this->slots() as $slot) {
            $slotDate = $slot->play_date instanceof Carbon ? $slot->play_date->format('Y-m-d') : (string) $slot->play_date;
            $session = $slot->relationLoaded('session')
                ? $slot->session
                : ($slot->relationLoaded('playSession') ? $slot->playSession : ($slot->session ?? null));
            $endTime = $session ? $session->end_time : '23:59:59';

            $slotEndDateTime = Carbon::parse("{$slotDate} {$endTime}", 'Asia/Jayapura');

            if ($slotEndDateTime->greaterThan($currentTime)) {
                $remaining++;
            }
        }

        return $remaining;
    }

    /**
     * Status lanjutan perpanjangan berdasarkan booking yang renewed_from_id mengarah ke booking ini:
     * - 'sudah_lanjut': ada booking perpanjangan berstatus paid
     * - 'perpanjangan_menunggu_bayar': ada booking perpanjangan pending_payment yang belum kedaluwarsa
     * - 'belum_lanjut': belum ada perpanjangan yang aktif/berhasil
     */
    public function continuationStatus(?Carbon $now = null): string
    {
        $currentTime = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');

        $renewals = $this->cachedRenewals ?? Booking::where('renewed_from_id', $this->booking->id)->get();

        foreach ($renewals as $renewal) {
            if ($renewal->status === BookingStatus::PAID) {
                return 'sudah_lanjut';
            }
        }

        foreach ($renewals as $renewal) {
            if ($renewal->status === BookingStatus::PENDING_PAYMENT) {
                if ($renewal->expires_at && $renewal->expires_at->greaterThan($currentTime)) {
                    return 'perpanjangan_menunggu_bayar';
                }
            }
        }

        return 'belum_lanjut';
    }

    /**
     * State siklus hidup paket Bulanan:
     * - 'selesai': remaining == 0
     * - 'terakhir_hari_ini': remaining > 0, slot terakhir adalah hari ini (Jayapura) dan sesi belum selesai
     * - 'hampir_habis': remaining <= settings.renew_notice_remaining dan remaining > 0
     * - 'aktif': remaining > settings.renew_notice_remaining
     */
    public function state(?Carbon $now = null): string
    {
        $currentTime = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $remaining = $this->remainingMeetings($currentTime);

        if ($remaining === 0) {
            return 'selesai';
        }

        $lastSlot = $this->lastSlot();
        if ($lastSlot) {
            $lastDate = $lastSlot->play_date instanceof Carbon ? $lastSlot->play_date->format('Y-m-d') : (string) $lastSlot->play_date;
            $todayJayapura = $currentTime->format('Y-m-d');

            if ($lastDate === $todayJayapura) {
                $session = $lastSlot->relationLoaded('session')
                    ? $lastSlot->session
                    : ($lastSlot->relationLoaded('playSession') ? $lastSlot->playSession : ($lastSlot->session ?? null));
                $endTime = $session ? $session->end_time : '23:59:59';
                $lastSlotEnd = Carbon::parse("{$lastDate} {$endTime}", 'Asia/Jayapura');
                if ($lastSlotEnd->greaterThan($currentTime)) {
                    return 'terakhir_hari_ini';
                }
            }
        }

        $noticeThreshold = Setting::getInt('renew_notice_remaining', 1);
        if ($remaining <= $noticeThreshold) {
            return 'hampir_habis';
        }

        return 'aktif';
    }

    /**
     * Cek apakah penanda "Sisa 1x" harus ditampilkan pada jadwal publik.
     * Tampil HANYA jika sisa pertemuan == 1 dan belum diperpanjang (belum_lanjut).
     * Hilang jika sudah diperpanjang (sudah_lanjut).
     */
    public function shouldShowRemainingOneBadge(?Carbon $now = null): bool
    {
        $currentTime = $now ? $now->copy()->setTimezone('Asia/Jayapura') : Carbon::now('Asia/Jayapura');
        $remaining = $this->remainingMeetings($currentTime);

        if ($remaining === 1 && $this->continuationStatus($currentTime) === 'belum_lanjut') {
            return true;
        }

        return false;
    }
}
