<?php

namespace App\Services;

use App\Enums\ApplicationStatus as S;
use App\Enums\Role;
use App\Models\ApplicationLog;
use App\Models\AuditLog;
use App\Models\LoanApplication;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mesin status (state machine) pengajuan kredit.
 * Seluruh perpindahan status wajib melalui kelas ini agar tercatat, ber-SLA, dan ternotifikasi.
 */
class ApplicationWorkflow
{
    /** Transisi yang diizinkan: from => [to => role yang boleh]. */
    public const TRANSITIONS = [
        'draft' => ['submitted' => ['nasabah'], 'cancelled' => ['nasabah']],
        'revision' => ['submitted' => ['nasabah'], 'cancelled' => ['nasabah', 'cs']],
        'submitted' => ['analysis' => ['cs'], 'revision' => ['cs'], 'rejected' => ['cs'], 'cancelled' => ['nasabah', 'cs']],
        'analysis' => ['approval' => ['analis'], 'revision' => ['analis']],
        'approval' => ['approved' => ['kepala_cabang', 'komite'], 'rejected' => ['kepala_cabang', 'komite'], 'analysis' => ['kepala_cabang', 'komite']],
        'approved' => ['contract' => ['legal'], 'cancelled' => ['legal']],
        'contract' => ['active' => ['teller']],
        'active' => ['paid_off' => ['teller', 'collection']],
    ];

    public function __construct(private BusinessCalendar $calendar) {}

    public function canTransition(LoanApplication $app, S $to, User $user): bool
    {
        $allowed = self::TRANSITIONS[$app->status->value][$to->value] ?? [];
        if (! in_array($user->role->value, $allowed, true)) {
            return false;
        }
        // Persetujuan berjenjang sesuai batas wewenang plafon
        if ($app->status === S::Approval && in_array($to, [S::Approved, S::Rejected], true)) {
            return $user->role === $app->requiredApproverRole();
        }

        return true;
    }

    public function transition(LoanApplication $app, S $to, User $user, ?string $notes = null, array $attributes = []): LoanApplication
    {
        if (! $this->canTransition($app, $to, $user)) {
            throw ValidationException::withMessages([
                'status' => "Perubahan status dari \"{$app->status->label()}\" ke \"{$to->label()}\" tidak diizinkan untuk peran Anda.",
            ]);
        }

        return DB::transaction(function () use ($app, $to, $user, $notes, $attributes) {
            $from = $app->status;
            $breached = $app->isSlaBreached();

            $app->fill($attributes);
            $app->status = $to;
            $app->status_changed_at = now();
            $app->sla_due_at = ($key = $to->slaKey())
                ? $this->calendar->addWorkingHours(now(), (int) Setting::get($key))
                : null;

            if ($to === S::Submitted && ! $app->submitted_at) {
                $app->submitted_at = now();
            }
            if ($to === S::Approval) {
                $app->required_approver_role = $app->finalAmount() > (float) Setting::get('limit_kepala_cabang')
                    ? Role::Komite->value
                    : Role::KepalaCabang->value;
            }
            $app->save();

            ApplicationLog::query()->create([
                'loan_application_id' => $app->id,
                'user_id' => $user->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'notes' => $notes,
                'sla_breached' => $breached,
            ]);

            AuditLog::record('application.status', $app, "Status {$app->number}: {$from->label()} → {$to->label()}", ['notes' => $notes]);

            $this->notifyCustomer($app, $notes);
            $this->notifyHandlers($app);

            return $app;
        });
    }

    /** Catat log awal pembuatan draft. */
    public function logCreated(LoanApplication $app, User $user): void
    {
        ApplicationLog::query()->create([
            'loan_application_id' => $app->id,
            'user_id' => $user->id,
            'to_status' => S::Draft->value,
            'notes' => 'Pengajuan dibuat.',
        ]);
    }

    private function notifyCustomer(LoanApplication $app, ?string $notes): void
    {
        $customer = $app->customer->user;
        [$subject, $body] = NotificationTemplate::render('status_changed', [
            'nama' => $customer->name,
            'nomor' => $app->number,
            'status' => $app->status->label(),
            'catatan' => $notes ?: '-',
        ], 'Status pengajuan {nomor}: {status}', 'Pengajuan kredit Anda nomor {nomor} kini berstatus "{status}". Catatan: {catatan}');

        $customer->notify(new GeneralNotification($subject, $body, route('customer.applications.show', $app), 'status'));
    }

    /** Beri tahu petugas di tahap berikutnya (cabang yang sama, atau komite). */
    private function notifyHandlers(LoanApplication $app): void
    {
        $roles = $app->status === S::Approval ? [$app->requiredApproverRole()] : $app->status->handlerRoles();
        if (! $roles || $app->status === S::Active) {
            return;
        }

        $users = User::query()
            ->whereIn('role', array_map(fn ($r) => $r->value, $roles))
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('branch_id', $app->branch_id)->orWhere('role', Role::Komite->value)->orWhereNull('branch_id'))
            ->get();

        foreach ($users as $u) {
            $u->notify(new GeneralNotification(
                "Tugas baru: {$app->number}",
                "Pengajuan {$app->number} ({$app->customer->user->name}) menunggu tindakan Anda pada tahap \"{$app->status->label()}\".",
                route('admin.applications.show', $app),
                'task',
            ));
        }
    }
}
