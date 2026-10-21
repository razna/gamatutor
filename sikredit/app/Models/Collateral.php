<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collateral extends Model
{
    public const TYPES = [
        'tanah_bangunan' => 'Tanah & Bangunan (SHM/SHGB)',
        'kendaraan' => 'Kendaraan Bermotor (BPKB)',
        'deposito' => 'Deposito / Tabungan',
        'mesin' => 'Mesin / Peralatan Usaha',
        'lainnya' => 'Lainnya',
    ];

    public const BINDINGS = [
        'HT' => 'Hak Tanggungan',
        'FIDUSIA' => 'Fidusia',
        'GADAI' => 'Gadai',
        'CESSIE' => 'Cessie',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'estimated_value' => 'float',
        'market_value' => 'float',
        'liquidation_value' => 'float',
        'appraised_at' => 'date',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function appraiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appraised_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
