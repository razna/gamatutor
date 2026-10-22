<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus as S;
use App\Models\Installment;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Notifications\GeneralNotification;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'kredit:reminders';

    protected $description = 'Kirim pengingat jatuh tempo (H-n, H-0) dan pemberitahuan keterlambatan (H+1)';

    public function handle(): int
    {
        $before = (int) Setting::get('reminder_days_before');
        $targets = [
            today()->addDays($before)->toDateString() => 'installment_reminder',
            today()->toDateString() => 'installment_reminder',
            today()->subDay()->toDateString() => 'installment_overdue',
        ];
        $sent = 0;

        foreach ($targets as $date => $event) {
            $items = Installment::query()->whereDate('due_date', $date)->whereIn('status', ['unpaid', 'partial'])
                ->where(fn ($q) => $q->whereNull('last_reminder_at')->orWhereDate('last_reminder_at', '<', today()))
                ->whereHas('application', fn ($q) => $q->status(S::Active))
                ->with('application.customer.user')->get();

            foreach ($items as $inst) {
                $app = $inst->application;
                $user = $app->customer->user;
                [$subject, $body] = NotificationTemplate::render($event, [
                    'nama' => $user->name,
                    'nomor' => $app->number,
                    'angsuran_ke' => $inst->number,
                    'jumlah' => rupiah($inst->remaining()),
                    'jatuh_tempo' => tanggal($inst->due_date),
                ], $event === 'installment_overdue' ? 'Angsuran {nomor} terlambat' : 'Pengingat angsuran {nomor}',
                    $event === 'installment_overdue'
                        ? 'Angsuran ke-{angsuran_ke} sebesar {jumlah} telah melewati jatuh tempo {jatuh_tempo}. Segera lakukan pembayaran untuk menghindari denda.'
                        : 'Angsuran ke-{angsuran_ke} sebesar {jumlah} akan jatuh tempo pada {jatuh_tempo}.');

                $user->notify(new GeneralNotification($subject, $body, route('customer.loans.show', $app), 'reminder'));
                $inst->update(['last_reminder_at' => now()]);
                $sent++;
            }
        }
        $this->info("{$sent} pengingat terkirim.");

        return self::SUCCESS;
    }
}
