<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus as S;
use App\Http\Controllers\Admin\ReportController;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Memastikan seluruh halaman dapat dirender untuk setiap peran dengan data demo. */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Notification::fake();
    }

    public function test_public_pages(): void
    {
        foreach (['/', '/produk', '/produk/KPR-01', '/simulasi', '/simulasi?amount=100000000&tenor=24&method=efektif&rate=10', '/cek-kelayakan', '/faq', '/kontak', '/syarat-ketentuan', '/masuk', '/daftar', '/lupa-sandi'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->post('/cek-kelayakan', ['product' => 5, 'age' => 30, 'income' => 15e6, 'installments' => 0, 'amount' => 50e6, 'tenor' => 24, 'work_years' => 3, 'has_bad_credit' => 0])
            ->assertOk()->assertSee('Berpotensi Layak');
    }

    public function test_customer_pages(): void
    {
        $loan = LoanApplication::query()->status(S::Active)->with('customer.user')->firstOrFail();
        $user = $loan->customer->user;
        $this->actingAs($user);

        foreach ([route('customer.dashboard'), route('customer.profile.edit'), route('customer.applications.index'), route('customer.applications.create'),
            route('customer.applications.show', $loan), route('customer.loans.index'), route('customer.loans.show', $loan),
            route('customer.tickets.index'), route('customer.tickets.create'), route('notifications.index'),
            route('print.sp3k', $loan).'?html=1', route('print.contract', $loan).'?html=1', route('print.schedule', $loan).'?html=1'] as $url) {
            $this->get($url)->assertOk();
        }
        $draft = LoanApplication::query()->status(S::Draft)->with('customer.user')->firstOrFail();
        $this->actingAs($draft->customer->user)->get(route('customer.applications.edit', $draft))->assertOk();
    }

    public function test_pdf_documents_render(): void
    {
        $loan = LoanApplication::query()->status(S::Active)->with('customer.user')->firstOrFail();
        $this->actingAs($loan->customer->user);
        $res = $this->get(route('print.schedule', $loan));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->get(route('print.receipt', Payment::query()->where('loan_application_id', $loan->id)->first()))->assertOk();
    }

    public function test_staff_pages_for_every_role(): void
    {
        $emails = ['admin@sikredit.test', 'komite@sikredit.test', 'cs.jkt@sikredit.test', 'analis.jkt@sikredit.test', 'kacab.jkt@sikredit.test',
            'legal.jkt@sikredit.test', 'teller.jkt@sikredit.test', 'collection.jkt@sikredit.test', 'analis.sby@sikredit.test'];

        foreach ($emails as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->actingAs($user);
            $this->get(route('admin.dashboard'))->assertOk();
            $this->get(route('admin.applications.index'))->assertOk();
            $this->get(route('admin.applications.index', ['scope' => 'all', 'sla' => 'breached']))->assertOk();
            $this->get(route('admin.loans.index'))->assertOk();
            $this->get(route('notifications.index'))->assertOk();

            foreach (LoanApplication::query()->visibleTo($user)->where('status', '!=', 'draft')->get() as $app) {
                $this->get(route('admin.applications.show', $app))->assertOk();
                if (in_array($app->status, [S::Active, S::PaidOff], true)) {
                    $this->get(route('admin.loans.show', $app))->assertOk();
                }
                if ($app->analysis) {
                    $this->get(route('print.memo', $app).'?html=1')->assertOk();
                }
            }
        }
    }

    public function test_management_pages(): void
    {
        $this->actingAs(User::query()->where('email', 'admin@sikredit.test')->first());
        foreach (['admin.products.index', 'admin.products.create', 'admin.branches.index', 'admin.document-types.index', 'admin.holidays.index',
            'admin.users.index', 'admin.users.create', 'admin.settings.edit', 'admin.audit', 'admin.collection', 'admin.tickets.index'] as $name) {
            $this->get(route($name))->assertOk();
        }
        $this->get(route('admin.products.edit', 1))->assertOk();
        $this->get(route('admin.users.edit', 3))->assertOk();
        $this->get(route('admin.tickets.show', Ticket::query()->first()))->assertOk();

        foreach (array_keys(ReportController::TYPES) as $type) {
            $this->get(route('admin.reports', ['type' => $type, 'from' => now()->subYears(2)->toDateString()]))->assertOk();
            $this->get(route('admin.reports', ['type' => $type, 'export' => 'print']))->assertOk();
            $csv = $this->get(route('admin.reports', ['type' => $type, 'export' => 'csv']));
            $csv->assertOk();
            $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'));
        }
    }
}
