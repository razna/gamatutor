<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus as S;
use App\Enums\Collectibility;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\LoanService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Notification::fake();
    }

    private function overdueLoan(): LoanApplication
    {
        return LoanApplication::query()->status(S::Active)->where('days_past_due', '>', 0)->with('product')->orderBy('days_past_due')->firstOrFail();
    }

    public function test_seeded_portfolio_has_consistent_collectibility(): void
    {
        foreach (LoanApplication::query()->status(S::Active)->get() as $loan) {
            $this->assertSame(Collectibility::fromDaysPastDue($loan->days_past_due)->value, $loan->collectibility);
        }
        $this->assertTrue(LoanApplication::query()->where('collectibility', 5)->exists(), 'Seeder harus memuat kredit macet');
    }

    public function test_payment_allocates_penalty_then_interest_then_principal(): void
    {
        $loan = $this->overdueLoan();
        $service = app(LoanService::class);
        $oldest = $loan->activeInstallments()->where('status', '!=', 'paid')->first();
        $this->assertGreaterThan(0, $oldest->penalty, 'Tunggakan harus dikenai denda');

        $teller = User::query()->where('role', 'teller')->where('branch_id', $loan->branch_id)->first();
        $amount = $oldest->remainingPenalty() + $oldest->remaining();
        $payment = $service->recordPayment($loan, $amount, $teller, ['paid_at' => today()->toDateString()]);

        $this->assertEqualsWithDelta($oldest->remainingPenalty(), $payment->penalty_paid, 0.01);
        $this->assertEqualsWithDelta($oldest->interest, $payment->interest_paid, 0.01);
        $this->assertEqualsWithDelta($oldest->principal, $payment->principal_paid, 0.01);
        $this->assertSame('paid', $oldest->refresh()->status);
    }

    public function test_overpayment_is_rejected(): void
    {
        $loan = LoanApplication::query()->status(S::Active)->firstOrFail();
        $teller = User::query()->where('role', 'teller')->first();

        $this->expectException(ValidationException::class);
        app(LoanService::class)->recordPayment($loan, 10_000_000_000, $teller);
    }

    public function test_restructure_reschedules_outstanding_and_resets_arrears(): void
    {
        $loan = $this->overdueLoan();
        $kacab = User::query()->where('role', 'kepala_cabang')->where('branch_id', $loan->branch_id)->first();

        $this->actingAs($kacab)->post(route('admin.loans.restructure', $loan), [
            'scheme' => 'perpanjangan_tenor', 'new_tenor' => 36, 'new_rate' => 9, 'reason' => 'Debitur terdampak penurunan omzet.',
        ])->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertSame(0, $loan->days_past_due);
        $this->assertSame(1, $loan->restructure_count);
        $this->assertSame(36, $loan->activeInstallments()->where('status', '!=', 'paid')->count());
        $this->assertTrue($loan->installments()->where('status', 'cancelled')->exists());
    }

    public function test_refresh_command_and_reminders_run(): void
    {
        $this->artisan('kredit:refresh')->assertSuccessful();

        $inst = LoanApplication::query()->status(S::Active)->where('days_past_due', 0)->firstOrFail()
            ->activeInstallments()->where('status', 'unpaid')->first();
        Carbon::setTestNow($inst->due_date->copy()->subDays(3)->setTime(8, 0));
        $this->artisan('kredit:reminders')->assertSuccessful();
        $this->assertNotNull($inst->refresh()->last_reminder_at);
        Carbon::setTestNow();
    }
}
