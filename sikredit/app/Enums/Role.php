<?php

namespace App\Enums;

enum Role: string
{
    case Nasabah = 'nasabah';
    case CustomerService = 'cs';
    case Analis = 'analis';
    case KepalaCabang = 'kepala_cabang';
    case Komite = 'komite';
    case Legal = 'legal';
    case Teller = 'teller';
    case Collection = 'collection';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Nasabah => 'Nasabah',
            self::CustomerService => 'Customer Service',
            self::Analis => 'Analis Kredit',
            self::KepalaCabang => 'Kepala Cabang',
            self::Komite => 'Komite Kredit',
            self::Legal => 'Admin Legal',
            self::Teller => 'Teller / Pencairan',
            self::Collection => 'Collection',
            self::Admin => 'Super Admin',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Nasabah;
    }

    public function isApprover(): bool
    {
        return in_array($this, [self::KepalaCabang, self::Komite], true);
    }

    /** @return array<int, self> */
    public static function staff(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => $r->isStaff()));
    }
}
