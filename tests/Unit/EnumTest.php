<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\PaymentStatus;
use App\Enums\SlotStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    /**
     * Uji UserRole enum dan nilainya.
     */
    public function test_user_role_values_and_labels(): void
    {
        $this->assertEquals('customer', UserRole::CUSTOMER->value);
        $this->assertEquals('admin', UserRole::ADMIN->value);
        $this->assertEquals('owner', UserRole::OWNER->value);

        $this->assertEquals('Customer', UserRole::CUSTOMER->label());
        $this->assertEquals('Admin', UserRole::ADMIN->label());
        $this->assertEquals('Owner', UserRole::OWNER->label());
    }

    /**
     * Uji BookingType WAJIB bernilai 'Harian' dan 'Bulanan' di UI.
     * Dilarang 'Sekali main' atau 'Paket bulanan'.
     */
    public function test_booking_type_labels(): void
    {
        $this->assertEquals('single', BookingType::SINGLE->value);
        $this->assertEquals('package', BookingType::PACKAGE->value);

        $this->assertEquals('Harian', BookingType::SINGLE->label());
        $this->assertEquals('Bulanan', BookingType::PACKAGE->label());

        $this->assertNotEquals('Sekali main', BookingType::SINGLE->label());
        $this->assertNotEquals('Paket bulanan', BookingType::PACKAGE->label());
    }

    /**
     * Uji BookingStatus enum.
     */
    public function test_booking_status_labels(): void
    {
        $this->assertEquals('pending_payment', BookingStatus::PENDING_PAYMENT->value);
        $this->assertEquals('paid', BookingStatus::PAID->value);
        $this->assertEquals('cancelled', BookingStatus::CANCELLED->value);
        $this->assertEquals('expired', BookingStatus::EXPIRED->value);

        $this->assertEquals('Menunggu Pembayaran', BookingStatus::PENDING_PAYMENT->label());
        $this->assertEquals('Dibayar', BookingStatus::PAID->label());
    }

    /**
     * Uji PaymentStatus enum.
     */
    public function test_payment_status_labels(): void
    {
        $this->assertEquals('pending', PaymentStatus::PENDING->value);
        $this->assertEquals('paid', PaymentStatus::PAID->value);
        $this->assertEquals('failed', PaymentStatus::FAILED->value);

        $this->assertEquals('Menunggu', PaymentStatus::PENDING->label());
        $this->assertEquals('Berhasil', PaymentStatus::PAID->label());
        $this->assertEquals('Gagal', PaymentStatus::FAILED->label());
    }

    /**
     * Uji SlotStatus enum.
     */
    public function test_slot_status_labels(): void
    {
        $this->assertEquals('kosong', SlotStatus::KOSONG->value);
        $this->assertEquals('menunggu_bayar', SlotStatus::MENUNGGU_BAYAR->value);
        $this->assertEquals('terisi', SlotStatus::TERISI->value);
        $this->assertEquals('ditutup', SlotStatus::DITUTUP->value);
        $this->assertEquals('lewat', SlotStatus::LEWAT->value);

        $this->assertEquals('Kosong', SlotStatus::KOSONG->label());
        $this->assertEquals('Menunggu Bayar', SlotStatus::MENUNGGU_BAYAR->label());
        $this->assertEquals('Terisi', SlotStatus::TERISI->label());
        $this->assertEquals('Ditutup', SlotStatus::DITUTUP->label());
        $this->assertEquals('Lewat', SlotStatus::LEWAT->label());
    }
}
