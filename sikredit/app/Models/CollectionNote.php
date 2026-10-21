<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionNote extends Model
{
    public const ACTIONS = [
        'telepon' => 'Telepon',
        'whatsapp' => 'WhatsApp / SMS',
        'kunjungan' => 'Kunjungan Lapangan',
        'sp1' => 'Surat Peringatan 1',
        'sp2' => 'Surat Peringatan 2',
        'sp3' => 'Surat Peringatan 3',
    ];

    protected $guarded = ['id'];

    protected $casts = ['promise_date' => 'date', 'promise_amount' => 'float'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }
}
