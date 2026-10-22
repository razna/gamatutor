<?php

namespace App\Services;

use App\Enums\ApplicationStatus as S;
use App\Enums\Collectibility;
use App\Models\AuditLog;
use App\Models\Installment;
use App\Models\LoanApplication;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\Restructure;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Operasional pinjaman aktif: pencairan, pembayaran, pelunasan, restrukturisasi, kolektibilitas. */
class LoanService
{
    public function __construct(
        private LoanCalculator $calculator,
        private ApplicationWorkflow $workflow,
        private BusinessCalendar $calendar,
    ) {}

    public function disburse(LoanApplication $app, User $teller, array $data): LoanApplication
    {
        return DB::transaction(function () use ($app, $teller, $data) {
            $amount = $app->finalAmount();
            $provision = round($amount * $app->product->provision_fee / 100, 2);
            $admin = $app->product->admin_fee;
            $disbursedAt = Carbon::parse($data['disbursed_at']);
            $firstDue = $disbursedAt->copy()->addMonthNoOverflow();

            $this->workflow->transition($app, S::Active, $teller, $data['notes'] ?? 'Dana telah dicairkan.', [
                'disbursed_by' => $teller->id,
                'disbursed_at' => $disbursedAt,
                'disbursement_bank' => $data['disbursement_bank'],
                'disbursement_account' => $data['disbursement_account'],
                'provision_amount' => $provision,
                'admin_amount' => $admin,
                'net_disbursed' => $amount - $provision - $admin,
                'first_due_date' => $firstDue,
                'outstanding_principal' => $amount,
                'collectibility' => Collectibility::Lancar->value,
                'days_past_due' => 0,
            ]);

            $this->createInstallments($app, $amount, $app->finalRate(), $app->finalTenor(), $firstDue, 1);
            AuditLog::record('loan.disburse', $app, "Pencairan {$app->number} sebesar ".rupiah($amount));

            return $app->refresh();
        });
    }

    private function createInstallments(LoanApplication $app, float $principal, float $rate, int $tenor, CarbonInterface $firstDue, int $startNumber): void
    {
        foreach ($this->calculator->schedule($principal, $rate, $tenor, $app->interest_method, $firstDue) as $row) {
            $app->installments()->create([
                'number' => $startNumber + $row['number'] - 1,
                'due_date' => $this->calendar->nextWorkingDay(Carbon::parse($row['due_date'])),
                'principal' => $row['principal'],
                'interest' => $row['interest'],
                'amount' => $row['amount'],
                'balance_after' => $row['balance_after'],
            ]);
        }
    }

    /** Hitung ulang denda, hari tunggakan, kolektibilitas, dan sisa pokok. */
    public function refresh(LoanApplication $app, ?CarbonInterface $asOf = null): LoanApplication
    {
        $asOf = Carbon::instance($asOf ?? today())->startOfDay();
        $rate = $app->product->penalty_rate;
        $maxDays = 0;

        foreach ($app->activeInstallments()->get() as $inst) {
            if ($inst->status === 'paid') {
                continue;
            }
            $days = $inst->due_date->lt($asOf) ? (int) $inst->due_date->diffInDays($asOf) : 0;
            $maxDays = max($maxDays, $days);
            $penalty = $this->calculator->penalty($inst->remaining(), $rate, $days);
            $inst->update(['penalty' => max($inst->penalty, $penalty)]);
        }

        $app->update([
            'days_past_due' => $maxDays,
            'collectibility' => Collectibility::fromDaysPastDue($maxDays)->value,
            'outstanding_principal' => $this->outstandingPrincipal($app),
        ]);

        return $app;
    }

    public function outstandingPrincipal(LoanApplication $app): float
    {
        return round($app->activeInstallments()->get()->sum(
            fn (Installment $i) => max(0, $i->principal - max(0, $i->paid_amount - $i->interest))
        ), 2);
    }

    /**
     * Catat pembayaran angsuran. Alokasi: denda → bunga → pokok, mulai angsuran tertua.
     */
    public function recordPayment(LoanApplication $app, float $amount, User $receiver, array $data = []): Payment
    {
        if ($app->status !== S::Active) {
            throw ValidationException::withMessages(['amount' => 'Pinjaman tidak dalam status aktif.']);
        }

        return DB::transaction(function () use ($app, $amount, $receiver, $data) {
            $paidAt = Carbon::parse($data['paid_at'] ?? today());
            $this->refresh($app, $paidAt);
            $remaining = round($amount, 2);
            $alloc = ['principal' => 0.0, 'interest' => 0.0, 'penalty' => 0.0];

            foreach ($app->activeInstallments()->where('status', '!=', 'paid')->get() as $inst) {
                if ($remaining <= 0) {
                    break;
                }
                // Denda
                $pay = min($remaining, $inst->remainingPenalty());
                $inst->penalty_paid += $pay;
                $alloc['penalty'] += $pay;
                $remaining -= $pay;

                // Bunga kemudian pokok
                $interestDue = max(0, $inst->interest - $inst->paid_amount);
                $payI = min($remaining, $interestDue);
                $remaining -= $payI;
                $principalDue = $inst->amount - $inst->paid_amount - $payI;
                $payP = min($remaining, max(0, $principalDue));
                $remaining -= $payP;

                $inst->paid_amount = round($inst->paid_amount + $payI + $payP, 2);
                $alloc['interest'] += $payI;
                $alloc['principal'] += $payP;

                $inst->status = $inst->remaining() <= 0 && $inst->remainingPenalty() <= 0 ? 'paid' : 'partial';
                if ($inst->status === 'paid') {
                    $inst->paid_at = $paidAt;
                }
                $inst->save();
            }

            if ($remaining > 0.009) {
                throw ValidationException::withMessages(['amount' => 'Nominal melebihi total kewajiban. Gunakan fitur pelunasan dipercepat.']);
            }

            $payment = $app->payments()->create([
                'receipt_number' => NumberGenerator::next('BYR', 'payments', 'receipt_number'),
                'amount' => $amount,
                'principal_paid' => round($alloc['principal'], 2),
                'interest_paid' => round($alloc['interest'], 2),
                'penalty_paid' => round($alloc['penalty'], 2),
                'type' => 'installment',
                'method' => $data['method'] ?? 'tunai',
                'reference' => $data['reference'] ?? null,
                'paid_at' => $paidAt,
                'received_by' => $receiver->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->refresh($app, $paidAt);
            $this->closeIfPaidOff($app, $receiver);
            $this->notifyPayment($app, $payment);
            AuditLog::record('loan.payment', $app, "Pembayaran {$payment->receipt_number} ".rupiah($amount));

            return $payment;
        });
    }

    /** Simulasi pelunasan dipercepat per tanggal tertentu. */
    public function payoffQuote(LoanApplication $app, ?CarbonInterface $asOf = null): array
    {
        $asOf = Carbon::instance($asOf ?? today())->startOfDay();
        $this->refresh($app, $asOf);
        $arrears = 0.0;
        $penalty = 0.0;
        $principal = 0.0;

        foreach ($app->activeInstallments()->where('status', '!=', 'paid')->get() as $inst) {
            $penalty += $inst->remainingPenalty();
            if ($inst->due_date->lte($asOf)) {
                $arrears += $inst->remaining(); // angsuran jatuh tempo dibayar penuh (pokok + bunga)
            } else {
                $principal += max(0, $inst->principal - max(0, $inst->paid_amount - $inst->interest));
            }
        }

        $feePct = (float) Setting::get('early_payoff_penalty');
        $fee = round($principal * $feePct / 100, 2);

        return [
            'as_of' => $asOf,
            'arrears' => round($arrears, 2),
            'penalty' => round($penalty, 2),
            'principal' => round($principal, 2),
            'fee_pct' => $feePct,
            'fee' => $fee,
            'total' => round($arrears + $penalty + $principal + $fee, 2),
        ];
    }

    public function payoff(LoanApplication $app, User $receiver, array $data): Payment
    {
        return DB::transaction(function () use ($app, $receiver, $data) {
            $paidAt = Carbon::parse($data['paid_at'] ?? today());
            $q = $this->payoffQuote($app, $paidAt);
            $interestPaid = 0.0;
            $principalPaid = 0.0;

            foreach ($app->activeInstallments()->where('status', '!=', 'paid')->get() as $inst) {
                if ($inst->due_date->lte($paidAt)) {
                    $interestPaid += max(0, $inst->interest - $inst->paid_amount);
                    $principalPaid += $inst->amount - $inst->paid_amount - max(0, $inst->interest - $inst->paid_amount);
                } else {
                    // Bunga angsuran mendatang dihapuskan
                    $paidPrincipal = max(0, $inst->paid_amount - $inst->interest);
                    $principalPaid += $inst->principal - $paidPrincipal;
                    $inst->interest = min($inst->interest, $inst->paid_amount);
                    $inst->amount = $inst->principal + $inst->interest;
                }
                $inst->paid_amount = $inst->amount;
                $inst->penalty_paid = $inst->penalty;
                $inst->status = 'paid';
                $inst->paid_at = $paidAt;
                $inst->save();
            }

            $payment = $app->payments()->create([
                'receipt_number' => NumberGenerator::next('BYR', 'payments', 'receipt_number'),
                'amount' => $q['total'],
                'principal_paid' => round($principalPaid, 2),
                'interest_paid' => round($interestPaid + $q['fee'], 2),
                'penalty_paid' => $q['penalty'],
                'type' => 'payoff',
                'method' => $data['method'] ?? 'tunai',
                'reference' => $data['reference'] ?? null,
                'paid_at' => $paidAt,
                'received_by' => $receiver->id,
                'notes' => 'Pelunasan dipercepat. Biaya pelunasan '.rupiah($q['fee']).'. '.($data['notes'] ?? ''),
            ]);

            $this->refresh($app, $paidAt);
            $this->closeIfPaidOff($app, $receiver);
            AuditLog::record('loan.payoff', $app, "Pelunasan dipercepat {$app->number} ".rupiah($q['total']));

            return $payment;
        });
    }

    public function restructure(LoanApplication $app, User $user, array $data): Restructure
    {
        return DB::transaction(function () use ($app, $user, $data) {
            $this->refresh($app);
            $unpaid = $app->activeInstallments()->where('status', '!=', 'paid')->get();
            if ($unpaid->isEmpty()) {
                throw ValidationException::withMessages(['new_tenor' => 'Tidak ada sisa kewajiban untuk direstrukturisasi.']);
            }

            // Pokok yang belum dibayar + bunga tertunggak dikapitalisasi; denda dihapuskan.
            $principal = 0.0;
            foreach ($unpaid as $inst) {
                $paidPrincipal = max(0, $inst->paid_amount - $inst->interest);
                $principal += $inst->principal - $paidPrincipal;
                if ($inst->due_date->lt(today())) {
                    $principal += max(0, $inst->interest - $inst->paid_amount);
                }
                $inst->update(['status' => 'cancelled']);
            }
            $principal = round($principal, 2);

            $record = $app->restructures()->create([
                'user_id' => $user->id,
                'scheme' => $data['scheme'],
                'outstanding_before' => $principal,
                'old_remaining_tenor' => $unpaid->count(),
                'new_tenor' => (int) $data['new_tenor'],
                'old_rate' => $app->finalRate(),
                'new_rate' => (float) $data['new_rate'],
                'reason' => $data['reason'],
            ]);

            $nextNumber = (int) $app->installments()->max('number') + 1;
            $firstDue = today()->addMonthNoOverflow()->day(min(28, $app->first_due_date?->day ?? today()->day));
            $this->createInstallments($app, $principal, (float) $data['new_rate'], (int) $data['new_tenor'], $firstDue, $nextNumber);

            $app->update([
                'approved_rate' => (float) $data['new_rate'],
                'restructure_count' => $app->restructure_count + 1,
            ]);
            $this->refresh($app);
            AuditLog::record('loan.restructure', $app, "Restrukturisasi {$app->number}: tenor baru {$data['new_tenor']} bln, bunga {$data['new_rate']}%");

            $app->customer->user->notify(new GeneralNotification(
                "Restrukturisasi kredit {$app->number}",
                "Kredit Anda telah direstrukturisasi dengan jangka waktu {$data['new_tenor']} bulan dan bunga {$data['new_rate']}% p.a. Silakan cek jadwal angsuran terbaru.",
                route('customer.loans.show', $app),
            ));

            return $record;
        });
    }

    private function closeIfPaidOff(LoanApplication $app, User $user): void
    {
        $open = $app->activeInstallments()->where('status', '!=', 'paid')->exists();
        if (! $open && $app->status === S::Active) {
            $this->workflow->transition($app, S::PaidOff, $user, 'Seluruh kewajiban telah lunas.', [
                'paid_off_at' => today(),
                'outstanding_principal' => 0,
                'collectibility' => Collectibility::Lancar->value,
                'days_past_due' => 0,
            ]);
        }
    }

    private function notifyPayment(LoanApplication $app, Payment $payment): void
    {
        $user = $app->customer->user;
        [$subject, $body] = NotificationTemplate::render('payment_received', [
            'nama' => $user->name,
            'nomor' => $app->number,
            'jumlah' => rupiah($payment->amount),
            'kuitansi' => $payment->receipt_number,
        ], 'Pembayaran diterima — {kuitansi}', 'Pembayaran sebesar {jumlah} untuk kredit {nomor} telah kami terima (No. {kuitansi}). Terima kasih.');

        $user->notify(new GeneralNotification($subject, $body, route('customer.loans.show', $app), 'payment'));
    }
}
