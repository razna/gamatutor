<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = ['event', 'subject', 'body'];

    public const EVENTS = [
        'status_changed' => 'Perubahan status pengajuan',
        'installment_reminder' => 'Pengingat jatuh tempo angsuran',
        'installment_overdue' => 'Pemberitahuan keterlambatan angsuran',
        'payment_received' => 'Pembayaran diterima',
    ];

    /** Render template dengan placeholder {nama}. */
    public static function render(string $event, array $data, string $fallbackSubject, string $fallbackBody): array
    {
        $tpl = static::query()->where('event', $event)->first();
        $subject = $tpl?->subject ?? $fallbackSubject;
        $body = $tpl?->body ?? $fallbackBody;
        $replace = [];
        foreach ($data as $k => $v) {
            $replace['{'.$k.'}'] = (string) $v;
        }

        return [strtr($subject, $replace), strtr($body, $replace)];
    }
}
