<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'date',
        'last_reminder_at' => 'datetime',
        'principal' => 'float',
        'interest' => 'float',
        'amount' => 'float',
        'balance_after' => 'float',
        'paid_amount' => 'float',
        'penalty' => 'float',
        'penalty_paid' => 'float',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function remaining(): float
    {
        return max(0, round($this->amount - $this->paid_amount, 2));
    }

    public function remainingPenalty(): float
    {
        return max(0, round($this->penalty - $this->penalty_paid, 2));
    }

    public function daysOverdue(): int
    {
        if ($this->status === 'paid' || $this->due_date->gte(today())) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(today());
    }

    public function statusBadge(): array
    {
        if ($this->status === 'paid') {
            return ['Lunas', 'bg-emerald-100 text-emerald-800'];
        }
        if ($this->status === 'cancelled') {
            return ['Direstrukturisasi', 'bg-slate-100 text-slate-500'];
        }
        if ($this->daysOverdue() > 0) {
            return ['Telat '.$this->daysOverdue().' hari', 'bg-rose-100 text-rose-800'];
        }

        return [$this->status === 'partial' ? 'Bayar Sebagian' : 'Belum Bayar', 'bg-slate-100 text-slate-700'];
    }
}
