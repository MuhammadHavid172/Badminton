<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case ADMIN = 'admin';
    case OWNER = 'owner';

    /**
     * Label representasi peran untuk kebutuhan UI / audit.
     */
    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::ADMIN => 'Admin',
            self::OWNER => 'Owner',
        };
    }
}
