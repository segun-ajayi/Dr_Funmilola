<?php

namespace App\Enums;

enum UserRole: string
{
    case Patient = 'patient';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case PowerAdmin = 'power_admin';

    public function isStaff(): bool
    {
        return $this !== self::Patient;
    }
}
