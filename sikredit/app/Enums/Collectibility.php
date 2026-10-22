<?php

namespace App\Enums;

/** Kolektibilitas kredit sesuai ketentuan OJK (berdasarkan hari tunggakan). */
enum Collectibility: int
{
    case Lancar = 1;
    case DalamPerhatianKhusus = 2;
    case KurangLancar = 3;
    case Diragukan = 4;
    case Macet = 5;

    public static function fromDaysPastDue(int $days): self
    {
        return match (true) {
            $days <= 0 => self::Lancar,
            $days <= 90 => self::DalamPerhatianKhusus,
            $days <= 120 => self::KurangLancar,
            $days <= 180 => self::Diragukan,
            default => self::Macet,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Lancar => 'Lancar',
            self::DalamPerhatianKhusus => 'Dalam Perhatian Khusus',
            self::KurangLancar => 'Kurang Lancar',
            self::Diragukan => 'Diragukan',
            self::Macet => 'Macet',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Lancar => 'bg-emerald-100 text-emerald-800',
            self::DalamPerhatianKhusus => 'bg-yellow-100 text-yellow-800',
            self::KurangLancar => 'bg-amber-100 text-amber-800',
            self::Diragukan => 'bg-orange-100 text-orange-800',
            self::Macet => 'bg-rose-100 text-rose-800',
        };
    }

    public function isNonPerforming(): bool
    {
        return $this->value >= 3;
    }
}
