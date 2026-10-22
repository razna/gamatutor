<?php

namespace Tests\Unit;

use App\Enums\Collectibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CollectibilityTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_collectibility_from_days_past_due(int $days, Collectibility $expected): void
    {
        $this->assertSame($expected, Collectibility::fromDaysPastDue($days));
    }

    public static function cases(): array
    {
        return [
            [0, Collectibility::Lancar],
            [1, Collectibility::DalamPerhatianKhusus],
            [90, Collectibility::DalamPerhatianKhusus],
            [91, Collectibility::KurangLancar],
            [120, Collectibility::KurangLancar],
            [121, Collectibility::Diragukan],
            [180, Collectibility::Diragukan],
            [181, Collectibility::Macet],
        ];
    }

    public function test_non_performing_is_kol_three_and_above(): void
    {
        $this->assertFalse(Collectibility::DalamPerhatianKhusus->isNonPerforming());
        $this->assertTrue(Collectibility::KurangLancar->isNonPerforming());
    }
}
