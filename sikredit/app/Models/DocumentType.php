<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DocumentType extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(CreditProduct::class)->withPivot('is_required');
    }
}
