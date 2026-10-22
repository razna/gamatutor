<?php

namespace App\Models;

use App\Enums\InterestMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditProduct extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'KPR' => 'Kredit Pemilikan Rumah',
        'KKB' => 'Kredit Kendaraan Bermotor',
        'KMK' => 'Kredit Modal Kerja',
        'KI' => 'Kredit Investasi',
        'KTA' => 'Kredit Tanpa Agunan',
        'KUR' => 'Kredit Usaha Rakyat',
        'MULTIGUNA' => 'Kredit Multiguna',
    ];

    protected $fillable = [
        'code', 'name', 'category', 'description', 'interest_rate', 'interest_method',
        'min_amount', 'max_amount', 'min_tenor', 'max_tenor', 'provision_fee', 'admin_fee',
        'penalty_rate', 'requires_collateral', 'max_ltv', 'requirements', 'is_active',
    ];

    protected $casts = [
        'interest_method' => InterestMethod::class,
        'interest_rate' => 'float',
        'min_amount' => 'float',
        'max_amount' => 'float',
        'provision_fee' => 'float',
        'admin_fee' => 'float',
        'penalty_rate' => 'float',
        'max_ltv' => 'float',
        'requires_collateral' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function documentTypes(): BelongsToMany
    {
        return $this->belongsToMany(DocumentType::class)->withPivot('is_required');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
