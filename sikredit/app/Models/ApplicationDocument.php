<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['verified_at' => 'datetime'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function statusBadge(): array
    {
        return match ($this->status) {
            'valid' => ['Valid', 'bg-emerald-100 text-emerald-800'],
            'invalid' => ['Tidak Valid', 'bg-rose-100 text-rose-800'],
            default => ['Belum Diperiksa', 'bg-slate-100 text-slate-700'],
        };
    }
}
