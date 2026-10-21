<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = [
        'tunai' => 'Tunai (Teller)',
        'transfer' => 'Transfer Bank',
        'autodebet' => 'Autodebet Rekening',
        'va' => 'Virtual Account',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'float',
        'principal_paid' => 'float',
        'interest_paid' => 'float',
        'penalty_paid' => 'float',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
