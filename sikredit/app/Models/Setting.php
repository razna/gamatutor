<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Nilai bawaan seluruh parameter sistem. */
    public const DEFAULTS = [
        // Bobot scoring 5C (total 100)
        'weight_character' => 25,
        'weight_capacity' => 30,
        'weight_capital' => 15,
        'weight_collateral' => 20,
        'weight_condition' => 10,
        // Batas kebijakan
        'max_dsr' => 40,
        // Batas wewenang persetujuan: s.d. nilai ini oleh Kepala Cabang, di atasnya oleh Komite
        'limit_kepala_cabang' => 500000000,
        // SLA per tahap (jam kerja kalender)
        'sla_submitted' => 24,
        'sla_analysis' => 72,
        'sla_approval' => 48,
        'sla_approved' => 72,
        'sla_contract' => 24,
        // Penagihan
        'reminder_days_before' => 3,
        'early_payoff_penalty' => 2, // % dari sisa pokok
        // Keamanan
        'staff_two_factor' => 0,
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings');
    }
}
