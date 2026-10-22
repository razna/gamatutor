<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const CATEGORIES = [
        'pengajuan' => 'Pengajuan Kredit',
        'angsuran' => 'Angsuran & Pembayaran',
        'dokumen' => 'Dokumen',
        'layanan' => 'Pelayanan Petugas',
        'lainnya' => 'Lainnya',
    ];

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest();
    }

    public function statusBadge(): array
    {
        return match ($this->status) {
            'answered' => ['Dijawab', 'bg-sky-100 text-sky-800'],
            'closed' => ['Selesai', 'bg-slate-100 text-slate-600'],
            default => ['Menunggu', 'bg-amber-100 text-amber-800'],
        };
    }
}
