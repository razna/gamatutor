<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'nik', 'kk_number', 'npwp', 'birth_place', 'birth_date', 'gender', 'marital_status',
        'dependents', 'education', 'mother_name', 'address', 'city', 'postal_code', 'home_status',
        'occupation_type', 'employer_name', 'job_title', 'work_years', 'business_field', 'office_address',
        'monthly_income', 'other_income', 'monthly_expense', 'existing_installments',
        'spouse_name', 'spouse_occupation', 'spouse_income',
        'emergency_name', 'emergency_relation', 'emergency_phone',
    ];

    protected $casts = [
        'nik' => 'encrypted',
        'kk_number' => 'encrypted',
        'npwp' => 'encrypted',
        'birth_date' => 'date',
        'monthly_income' => 'float',
        'other_income' => 'float',
        'monthly_expense' => 'float',
        'existing_installments' => 'float',
        'spouse_income' => 'float',
    ];

    public const OCCUPATIONS = [
        'karyawan' => 'Karyawan Swasta',
        'pns' => 'PNS / TNI / Polri',
        'wiraswasta' => 'Wiraswasta / Pengusaha',
        'profesional' => 'Profesional',
        'lainnya' => 'Lainnya',
    ];

    protected static function booted(): void
    {
        static::saving(function (Customer $c) {
            $c->nik_hash = $c->nik ? hash_hmac('sha256', $c->nik, config('app.key')) : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function totalIncome(): float
    {
        return $this->monthly_income + $this->other_income + $this->spouse_income;
    }

    public function maskedNik(): string
    {
        return $this->nik ? substr($this->nik, 0, 4).str_repeat('•', 8).substr($this->nik, -4) : '-';
    }

    /** Field wajib agar nasabah dapat mengajukan kredit. */
    public function isComplete(): bool
    {
        foreach (['nik', 'birth_date', 'address', 'city', 'mother_name', 'occupation_type', 'monthly_income', 'emergency_name', 'emergency_phone'] as $f) {
            if (blank($this->{$f})) {
                return false;
            }
        }

        return true;
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }
}
