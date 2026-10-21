<?php

namespace App\Enums;

enum InterestMethod: string
{
    case Flat = 'flat';
    case Efektif = 'efektif';
    case Anuitas = 'anuitas';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Flat',
            self::Efektif => 'Efektif (Menurun)',
            self::Anuitas => 'Anuitas',
        };
    }
}
