<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditAnalysis extends Model
{
    public const ASPECTS = [
        'character' => ['Character', 'Watak, reputasi, riwayat kredit (SLIK), dan itikad membayar.'],
        'capacity' => ['Capacity', 'Kemampuan membayar dari penghasilan/arus kas usaha (DSR).'],
        'capital' => ['Capital', 'Modal/kekayaan bersih dan uang muka yang dimiliki pemohon.'],
        'collateral' => ['Collateral', 'Nilai dan kualitas agunan serta kemudahan pengikatannya.'],
        'condition' => ['Condition', 'Kondisi ekonomi, sektor usaha, dan stabilitas pekerjaan.'],
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'net_income' => 'float',
        'new_installment' => 'float',
        'dsr' => 'float',
        'ltv' => 'float',
        'total_score' => 'float',
        'recommended_amount' => 'float',
        'recommended_rate' => 'float',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }
}
