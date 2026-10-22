<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\Collectibility;
use App\Enums\InterestMethod;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanApplication extends Model
{
    use HasFactory;

    public const PURPOSES = [
        'pembelian_rumah' => 'Pembelian Rumah / Properti',
        'renovasi' => 'Renovasi Rumah',
        'kendaraan' => 'Pembelian Kendaraan',
        'modal_kerja' => 'Modal Kerja Usaha',
        'investasi' => 'Investasi / Pembelian Aset Usaha',
        'pendidikan' => 'Biaya Pendidikan',
        'kesehatan' => 'Biaya Kesehatan',
        'konsumtif' => 'Kebutuhan Konsumtif Lainnya',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'status' => ApplicationStatus::class,
        'interest_method' => InterestMethod::class,
        'amount' => 'float',
        'interest_rate' => 'float',
        'approved_amount' => 'float',
        'approved_rate' => 'float',
        'provision_amount' => 'float',
        'admin_amount' => 'float',
        'net_disbursed' => 'float',
        'outstanding_principal' => 'float',
        'disbursement_account' => 'encrypted',
        'status_changed_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'akad_scheduled_at' => 'datetime',
        'akad_date' => 'date',
        'disbursed_at' => 'date',
        'first_due_date' => 'date',
        'paid_off_at' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CreditProduct::class, 'credit_product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function legalOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legal_id');
    }

    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function collaterals(): HasMany
    {
        return $this->hasMany(Collateral::class);
    }

    public function survey(): HasOne
    {
        return $this->hasOne(Survey::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(CreditAnalysis::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApplicationLog::class)->latest('id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('number');
    }

    public function activeInstallments(): HasMany
    {
        return $this->hasMany(Installment::class)->where('status', '!=', 'cancelled')->orderBy('number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at')->latest('id');
    }

    public function collectionNotes(): HasMany
    {
        return $this->hasMany(CollectionNote::class)->latest();
    }

    public function restructures(): HasMany
    {
        return $this->hasMany(Restructure::class)->latest();
    }

    // ---- Scopes -----------------------------------------------------------------------------

    /** Batasi data sesuai cabang petugas. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if (! $user->isStaff()) {
            return $q->whereHas('customer', fn ($c) => $c->where('user_id', $user->id));
        }

        return $user->seesAllBranches() ? $q : $q->where('branch_id', $user->branch_id);
    }

    public function scopeStatus(Builder $q, ApplicationStatus ...$statuses): Builder
    {
        return $q->whereIn('status', array_map(fn ($s) => $s->value, $statuses));
    }

    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")
                ->orWhereHas('customer.user', fn ($u) => $u->where('name', 'like', "%{$s}%"))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['product'] ?? null, fn ($q, $p) => $q->where('credit_product_id', $p))
            ->when($f['branch'] ?? null, fn ($q, $b) => $q->where('branch_id', $b))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }

    // ---- Helpers ----------------------------------------------------------------------------

    public function finalAmount(): float
    {
        return $this->approved_amount ?? $this->amount;
    }

    public function finalTenor(): int
    {
        return $this->approved_tenor ?? $this->tenor;
    }

    public function finalRate(): float
    {
        return $this->approved_rate ?? $this->interest_rate;
    }

    public function collectibilityEnum(): ?Collectibility
    {
        return $this->collectibility ? Collectibility::from($this->collectibility) : null;
    }

    public function isSlaBreached(): bool
    {
        return $this->sla_due_at !== null && $this->status->slaKey() !== null && now()->greaterThan($this->sla_due_at);
    }

    public function requiredApproverRole(): Role
    {
        return $this->required_approver_role ? Role::from($this->required_approver_role) : Role::KepalaCabang;
    }

    /** Dokumen wajib yang belum diunggah. */
    public function missingRequiredDocuments()
    {
        $uploaded = $this->documents->pluck('document_type_id')->all();

        return $this->product->documentTypes
            ->filter(fn ($d) => $d->pivot->is_required && ! in_array($d->id, $uploaded))
            ->values();
    }

    public function totalCollateralValue(): float
    {
        return (float) $this->collaterals->sum(fn ($c) => $c->market_value ?? $c->estimated_value);
    }

    public function nextInstallment(): ?Installment
    {
        return $this->activeInstallments->first(fn ($i) => $i->status !== 'paid');
    }

    public function totalArrears(): float
    {
        return (float) $this->activeInstallments
            ->filter(fn ($i) => $i->status !== 'paid' && $i->due_date->lt(today()))
            ->sum(fn ($i) => $i->remaining() + $i->remainingPenalty());
    }
}
