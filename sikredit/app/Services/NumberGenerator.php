<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    /** Contoh: KRD/2026/10/000123 */
    public static function next(string $prefix, string $table, string $column): string
    {
        $base = $prefix.'/'.now()->format('Y/m').'/';
        $last = DB::table($table)->where($column, 'like', $base.'%')->orderByDesc($column)->value($column);
        $seq = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        return $base.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
