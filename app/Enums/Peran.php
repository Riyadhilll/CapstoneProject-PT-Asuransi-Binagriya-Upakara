<?php
namespace App\Enums;

enum Peran: string
{
    case Petugas = 'petugas';
    case Pic = 'pic';
    case Pimpinan = 'pimpinan';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Petugas => 'Petugas Pelayanan',
            self::Pic => 'PIC',
            self::Pimpinan => 'Pimpinan',
            self::Admin => 'Admin',
        };
    }
}
