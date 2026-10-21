<?php

namespace Tests\Unit;

use App\Services\CreditScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditScoringTest extends TestCase
{
    use RefreshDatabase;

    private CreditScoringService $scoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scoring = app(CreditScoringService::class);
    }

    private function scores(int $v): array
    {
        return array_fill_keys(CreditScoringService::ASPECTS, $v);
    }

    public function test_weighted_score_uses_default_weights(): void
    {
        // bobot default: 25/30/15/20/10
        $r = $this->scoring->score(['character' => 100, 'capacity' => 80, 'capital' => 60, 'collateral' => 70, 'condition' => 50]);

        $this->assertEqualsWithDelta(77, $r['base'], 0.01); // 25 + 24 + 9 + 14 + 5
        $this->assertSame('B', $r['grade']);
        $this->assertSame('approve', $r['recommendation']);
    }

    public function test_grade_boundaries(): void
    {
        $this->assertSame('A', $this->scoring->grade(85));
        $this->assertSame('B', $this->scoring->grade(84.99));
        $this->assertSame('C', $this->scoring->grade(65));
        $this->assertSame('D', $this->scoring->grade(50));
        $this->assertSame('E', $this->scoring->grade(49.9));
    }

    public function test_bad_slik_history_is_not_eligible(): void
    {
        $r = $this->scoring->score($this->scores(95), slikCollectibility: 3);

        $this->assertFalse($r['eligible']);
        $this->assertSame('reject', $r['recommendation']);
    }

    public function test_high_dsr_reduces_score(): void
    {
        $ok = $this->scoring->score($this->scores(80), 1, 30);
        $high = $this->scoring->score($this->scores(80), 1, 55);

        $this->assertEquals(80, $ok['total']);
        $this->assertEquals(65, $high['total']);
        $this->assertNotEmpty($high['adjustments']);
    }

    public function test_ltv_above_product_limit_reduces_score(): void
    {
        $r = $this->scoring->score($this->scores(80), 1, 20, ltv: 90, maxLtv: 80);
        $this->assertEquals(70, $r['total']);
    }

    public function test_dsr_and_ltv_ratios(): void
    {
        $this->assertEquals(40.0, $this->scoring->dsr(10_000_000, 1_000_000, 3_000_000));
        $this->assertEquals(80.0, $this->scoring->ltv(400_000_000, 500_000_000));
        $this->assertNull($this->scoring->ltv(1, 0));
    }
}
