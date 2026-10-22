<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Credit scoring berbobot berbasis analisis 5C, dengan penyesuaian kebijakan
 * (riwayat SLIK OJK, Debt Service Ratio, dan Loan to Value).
 */
class CreditScoringService
{
    public const ASPECTS = ['character', 'capacity', 'capital', 'collateral', 'condition'];

    public function weights(): array
    {
        $w = [];
        foreach (self::ASPECTS as $a) {
            $w[$a] = (float) Setting::get('weight_'.$a);
        }

        return $w;
    }

    /**
     * @param  array<string,int|float>  $scores  nilai 0-100 untuk tiap aspek 5C
     * @return array{base: float, adjustments: array<int,string>, total: float, grade: string, recommendation: string, eligible: bool}
     */
    public function score(array $scores, int $slikCollectibility = 1, float $dsr = 0, ?float $ltv = null, ?float $maxLtv = null): array
    {
        $weights = $this->weights();
        $totalWeight = array_sum($weights) ?: 100;

        $base = 0.0;
        foreach (self::ASPECTS as $a) {
            $base += max(0, min(100, (float) ($scores[$a] ?? 0))) * $weights[$a];
        }
        $base = round($base / $totalWeight, 2);

        $total = $base;
        $adjustments = [];
        $eligible = true;

        if ($slikCollectibility >= 3) {
            $eligible = false;
            $adjustments[] = "Riwayat SLIK kolektibilitas {$slikCollectibility} (non-performing) — tidak memenuhi kebijakan.";
            $total -= 30;
        } elseif ($slikCollectibility === 2) {
            $adjustments[] = 'Riwayat SLIK kolektibilitas 2 (DPK): -10 poin.';
            $total -= 10;
        }

        $maxDsr = (float) Setting::get('max_dsr');
        if ($dsr > $maxDsr) {
            $adjustments[] = sprintf('DSR %.2f%% melebihi batas %.0f%%: -15 poin.', $dsr, $maxDsr);
            $total -= 15;
            if ($dsr > $maxDsr + 20) {
                $eligible = false;
            }
        }

        if ($ltv !== null && $maxLtv !== null && $ltv > $maxLtv) {
            $adjustments[] = sprintf('LTV %.2f%% melebihi batas produk %.0f%%: -10 poin.', $ltv, $maxLtv);
            $total -= 10;
        }

        $total = round(max(0, min(100, $total)), 2);
        $grade = $this->grade($total);

        return [
            'base' => $base,
            'adjustments' => $adjustments,
            'total' => $total,
            'grade' => $grade,
            'recommendation' => ($eligible && in_array($grade, ['A', 'B', 'C'], true)) ? 'approve' : 'reject',
            'eligible' => $eligible,
        ];
    }

    public function grade(float $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 75 => 'B',
            $score >= 65 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }

    /** Debt Service Ratio: (cicilan berjalan + cicilan baru) / penghasilan bersih x 100. */
    public function dsr(float $income, float $existingInstallments, float $newInstallment): float
    {
        return $income <= 0 ? 100.0 : round(($existingInstallments + $newInstallment) / $income * 100, 2);
    }

    /** Loan to Value: plafon / nilai agunan x 100. */
    public function ltv(float $amount, float $collateralValue): ?float
    {
        return $collateralValue <= 0 ? null : round($amount / $collateralValue * 100, 2);
    }

    public static function gradeDescription(string $grade): string
    {
        return [
            'A' => 'Sangat Baik — risiko sangat rendah',
            'B' => 'Baik — risiko rendah',
            'C' => 'Cukup — risiko moderat, dapat dipertimbangkan',
            'D' => 'Kurang — risiko tinggi',
            'E' => 'Buruk — tidak direkomendasikan',
        ][$grade] ?? '-';
    }
}
