<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restructure extends Model
{
    public const SCHEMES = [
        'perpanjangan_tenor' => 'Perpanjangan Jangka Waktu (Rescheduling)',
        'penurunan_bunga' => 'Penurunan Suku Bunga (Reconditioning)',
        'kombinasi' => 'Kombinasi',
    ];

    protected $guarded = ['id'];

    protected $casts = ['outstanding_before' => 'float', 'old_rate' => 'float', 'new_rate' => 'float'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
