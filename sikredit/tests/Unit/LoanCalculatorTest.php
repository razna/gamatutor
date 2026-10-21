<?php

namespace Tests\Unit;

use App\Enums\InterestMethod;
use App\Services\LoanCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LoanCalculatorTest extends TestCase
{
    private LoanCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new LoanCalculator;
    }

    public function test_flat_interest_has_equal_installments(): void
    {
        // 12 jt, 12% p.a., 12 bulan: pokok 1 jt + bunga 120 rb = 1,12 jt per bulan
        $rows = $this->calc->schedule(12_000_000, 12, 12, InterestMethod::Flat);

        $this->assertCount(12, $rows);
        foreach ($rows as $r) {
            $this->assertEqualsWithDelta(1_120_000, $r['amount'], 0.01);
            $this->assertEqualsWithDelta(120_000, $r['interest'], 0.01);
        }
        $this->assertEquals(0, end($rows)['balance_after']);
    }

    public function test_effective_interest_declines_each_month(): void
    {
        $rows = $this->calc->schedule(12_000_000, 12, 12, InterestMethod::Efektif);

        $this->assertEqualsWithDelta(1_120_000, $rows[0]['amount'], 0.01); // 1 jt + 1% x 12 jt
        $this->assertEqualsWithDelta(1_010_000, $rows[11]['amount'], 0.01); // 1 jt + 1% x 1 jt
        for ($i = 1; $i < 12; $i++) {
            $this->assertLessThan($rows[$i - 1]['amount'], $rows[$i]['amount']);
        }
        $this->assertEqualsWithDelta(780_000, array_sum(array_column($rows, 'interest')), 0.01);
    }

    public function test_annuity_payment_matches_formula(): void
    {
        // 100 jt, 12% p.a., 12 bulan => angsuran anuitas 8.884.878,87
        $this->assertEqualsWithDelta(8_884_878.87, $this->calc->annuityPayment(100_000_000, 12, 12), 0.01);

        $rows = $this->calc->schedule(100_000_000, 12, 12, InterestMethod::Anuitas);
        foreach (array_slice($rows, 0, 11) as $r) {
            $this->assertEqualsWithDelta(8_884_878.87, $r['amount'], 0.02);
        }
        // Porsi pokok makin besar, porsi bunga makin kecil
        $this->assertGreaterThan($rows[0]['principal'], $rows[11]['principal']);
        $this->assertLessThan($rows[0]['interest'], $rows[11]['interest']);
    }

    #[DataProvider('methods')]
    public function test_principal_is_fully_repaid(InterestMethod $method): void
    {
        $rows = $this->calc->schedule(123_456_789, 9.75, 37, $method);

        $this->assertEqualsWithDelta(123_456_789, array_sum(array_column($rows, 'principal')), 0.01);
        $this->assertEquals(0, end($rows)['balance_after']);
    }

    public static function methods(): array
    {
        return [[InterestMethod::Flat], [InterestMethod::Efektif], [InterestMethod::Anuitas]];
    }

    public function test_zero_interest_annuity(): void
    {
        $rows = $this->calc->schedule(1_200_000, 0, 12, InterestMethod::Anuitas);
        $this->assertEqualsWithDelta(100_000, $rows[0]['amount'], 0.01);
    }

    public function test_due_dates_follow_months_without_overflow(): void
    {
        $rows = $this->calc->schedule(3_000_000, 12, 3, InterestMethod::Flat, Carbon::parse('2026-01-31'));

        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], array_column($rows, 'due_date'));
    }

    public function test_summary_includes_fees(): void
    {
        $s = $this->calc->summary(100_000_000, 12, 12, InterestMethod::Flat, 1, 500_000);

        $this->assertEquals(1_000_000, $s['provision']);
        $this->assertEquals(98_500_000, $s['net_received']);
        $this->assertEqualsWithDelta(12_000_000, $s['total_interest'], 0.01);
    }

    public function test_penalty_is_daily_rate_times_days(): void
    {
        $this->assertEquals(10_000, $this->calc->penalty(1_000_000, 0.1, 10));
        $this->assertEquals(0, $this->calc->penalty(1_000_000, 0.1, 0));
    }
}
