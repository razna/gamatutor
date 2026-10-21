<?php

namespace App\Services;

use App\Enums\InterestMethod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Perhitungan jadwal angsuran untuk metode bunga flat, efektif (menurun), dan anuitas.
 * Suku bunga dinyatakan dalam persen per tahun.
 */
class LoanCalculator
{
    /**
     * @return array<int, array{number:int, due_date:?string, principal:float, interest:float, amount:float, balance_after:float}>
     */
    public function schedule(float $principal, float $annualRate, int $tenor, InterestMethod|string $method, ?CarbonInterface $firstDueDate = null): array
    {
        $method = $method instanceof InterestMethod ? $method : InterestMethod::from($method);
        if ($principal <= 0 || $tenor <= 0) {
            return [];
        }

        $r = $annualRate / 100 / 12;
        $rows = [];
        $balance = $principal;
        $start = $firstDueDate ? CarbonImmutable::instance($firstDueDate) : null;

        $annuity = $method === InterestMethod::Anuitas ? $this->annuityPayment($principal, $annualRate, $tenor) : 0.0;
        $flatPrincipal = round($principal / $tenor, 2);
        $flatInterest = round($principal * $r, 2);

        for ($i = 1; $i <= $tenor; $i++) {
            $last = $i === $tenor;
            switch ($method) {
                case InterestMethod::Flat:
                    $interest = $flatInterest;
                    $p = $last ? $balance : $flatPrincipal;
                    break;
                case InterestMethod::Efektif:
                    $interest = round($balance * $r, 2);
                    $p = $last ? $balance : $flatPrincipal;
                    break;
                default: // Anuitas
                    $interest = round($balance * $r, 2);
                    $p = $last ? $balance : round($annuity - $interest, 2);
            }

            $balance = round($balance - $p, 2);
            $rows[] = [
                'number' => $i,
                'due_date' => $start?->addMonthsNoOverflow($i - 1)->toDateString(),
                'principal' => round($p, 2),
                'interest' => $interest,
                'amount' => round($p + $interest, 2),
                'balance_after' => max(0, $balance),
            ];
        }

        return $rows;
    }

    public function annuityPayment(float $principal, float $annualRate, int $tenor): float
    {
        $r = $annualRate / 100 / 12;
        if ($r == 0.0) {
            return round($principal / $tenor, 2);
        }

        return round($principal * $r / (1 - (1 + $r) ** -$tenor), 2);
    }

    /** Ringkasan simulasi: angsuran pertama, terbesar, terkecil, total bunga, total bayar. */
    public function summary(float $principal, float $annualRate, int $tenor, InterestMethod|string $method, float $provisionPct = 0, float $adminFee = 0): array
    {
        $rows = $this->schedule($principal, $annualRate, $tenor, $method);
        $amounts = array_column($rows, 'amount');
        $interest = array_sum(array_column($rows, 'interest'));
        $provision = round($principal * $provisionPct / 100, 2);

        return [
            'first_installment' => $amounts[0] ?? 0,
            'max_installment' => $amounts ? max($amounts) : 0,
            'min_installment' => $amounts ? min($amounts) : 0,
            'total_interest' => round($interest, 2),
            'total_payment' => round($principal + $interest, 2),
            'provision' => $provision,
            'admin_fee' => $adminFee,
            'net_received' => round($principal - $provision - $adminFee, 2),
            'rows' => $rows,
        ];
    }

    /** Denda keterlambatan: persen per hari x tunggakan x hari terlambat. */
    public function penalty(float $overdueAmount, float $dailyRatePct, int $daysLate): float
    {
        return $daysLate <= 0 ? 0.0 : round($overdueAmount * $dailyRatePct / 100 * $daysLate, 2);
    }
}
