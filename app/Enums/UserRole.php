<?php

namespace App\Enums;

/**
 * The two application roles. Administrators manage accounts and are the only
 * ones who may delete content; employees manage content in their modules.
 */
enum UserRole: string
{
    case Administrator = 'administrator';
    case Pegawai = 'pegawai';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Pegawai => 'Pegawai UPTD',
        };
    }
}
