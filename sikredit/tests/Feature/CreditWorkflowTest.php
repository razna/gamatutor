<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus as S;
use App\Enums\Role;
use App\Models\Branch;
use App\Models\CreditProduct;
use App\Models\LoanApplication;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Skenario end-to-end: registrasi nasabah hingga kredit lunas, melalui seluruh peran. */
class CreditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([MasterDataSeeder::class, UserSeeder::class]);
        Storage::fake('local');
        Notification::fake();
    }

    private function staff(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function profile(): array
    {
        return [
            'name' => 'Nasabah Uji', 'phone' => '081234567890', 'nik' => '3171012345678901', 'kk_number' => '3171012345678902',
            'birth_place' => 'Jakarta', 'birth_date' => '1990-05-10', 'gender' => 'L', 'marital_status' => 'lajang', 'dependents' => 0,
            'mother_name' => 'Ibu Uji', 'address' => 'Jl. Uji No. 1', 'city' => 'Jakarta', 'home_status' => 'milik_sendiri',
            'occupation_type' => 'karyawan', 'employer_name' => 'PT Uji Coba', 'work_years' => 5,
            'monthly_income' => 20_000_000, 'monthly_expense' => 6_000_000,
            'emergency_name' => 'Kakak Uji', 'emergency_relation' => 'Kakak', 'emergency_phone' => '081298765432',
        ];
    }

    /** Buat nasabah + pengajuan terkirim (status Submitted). */
    private function submittedApplication(string $productCode = 'KTA-01', float $amount = 50_000_000, int $tenor = 24): LoanApplication
    {
        $this->post('/daftar', [
            'name' => 'Nasabah Uji', 'email' => 'uji@contoh.test', 'phone' => '081234567890',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'terms' => '1',
        ])->assertRedirect(route('customer.profile.edit'));

        $this->put(route('customer.profile.update'), $this->profile())->assertSessionHasNoErrors();

        $product = CreditProduct::query()->where('code', $productCode)->firstOrFail();
        $payload = [
            'credit_product_id' => $product->id, 'branch_id' => Branch::query()->where('code', 'JKT01')->value('id'),
            'amount' => $amount, 'tenor' => $tenor, 'purpose' => 'pendidikan',
        ];
        if ($product->requires_collateral) {
            $payload['collaterals'] = [['type' => 'tanah_bangunan', 'description' => 'Rumah', 'estimated_value' => $amount * 2]];
        }
        $this->post(route('customer.applications.store'), $payload)->assertSessionHasNoErrors();
        $app = LoanApplication::query()->latest('id')->firstOrFail();
        $this->assertSame(S::Draft, $app->status);

        // Tidak bisa dikirim sebelum dokumen wajib lengkap
        $this->post(route('customer.applications.submit', $app), ['agreement' => '1'])->assertSessionHasErrors('documents');

        foreach ($product->documentTypes as $type) {
            $this->post(route('customer.applications.documents.store', $app), [
                'document_type_id' => $type->id,
                'file' => UploadedFile::fake()->create(strtolower($type->code).'.pdf', 200, 'application/pdf'),
            ])->assertSessionHasNoErrors();
        }
        $this->post(route('customer.applications.submit', $app), ['agreement' => '1'])->assertSessionHasNoErrors();
        $app->refresh();
        $this->assertSame(S::Submitted, $app->status);
        $this->assertNotNull($app->sla_due_at);
        auth()->logout();

        return $app;
    }

    public function test_full_credit_lifecycle_from_application_to_paid_off(): void
    {
        $app = $this->submittedApplication();
        $customer = $app->customer->user;

        // 1. Customer Service memverifikasi dokumen
        $cs = $this->staff('cs.jkt@sikredit.test');
        $analyst = $this->staff('analis.jkt@sikredit.test');
        $this->actingAs($cs)->post(route('admin.process.verify', $app), ['analyst_id' => $analyst->id])->assertSessionHasErrors('analyst_id');
        foreach ($app->documents as $doc) {
            $this->actingAs($cs)->post(route('admin.process.document', [$app, $doc]), ['status' => 'valid'])->assertSessionHasNoErrors();
        }
        $this->actingAs($cs)->post(route('admin.process.verify', $app), ['analyst_id' => $analyst->id])->assertSessionHasNoErrors();
        $this->assertSame(S::Analysis, $app->refresh()->status);

        // 2. Analis: survei + analisis 5C + rekomendasi
        $this->actingAs($analyst)->post(route('admin.process.submit-approval', $app), ['recommendation' => 'approve'])->assertSessionHasErrors('recommendation');
        $this->actingAs($analyst)->post(route('admin.process.survey', $app), [
            'visit_date' => today()->toDateString(), 'address_verified' => 1, 'income_verified' => 1, 'business_verified' => 1,
            'photos' => [UploadedFile::fake()->image('rumah.jpg')],
        ])->assertSessionHasNoErrors();
        $this->actingAs($analyst)->post(route('admin.process.analyze', $app), [
            'character_score' => 85, 'capacity_score' => 80, 'capital_score' => 75, 'collateral_score' => 70, 'condition_score' => 80,
            'slik_collectibility' => 1, 'recommended_amount' => 50_000_000, 'recommended_tenor' => 24, 'recommended_rate' => 13.5,
            'summary' => 'Layak.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('B', $app->refresh()->analysis->grade);
        $this->actingAs($analyst)->post(route('admin.process.submit-approval', $app), ['recommendation' => 'approve'])->assertSessionHasNoErrors();
        $app->refresh();
        $this->assertSame(S::Approval, $app->status);
        $this->assertSame(Role::KepalaCabang->value, $app->required_approver_role);

        // 3. Analis tidak boleh memutus; Kepala Cabang menyetujui
        $this->actingAs($analyst)->post(route('admin.process.decide', $app), ['decision' => 'approve', 'notes' => 'x'])->assertForbidden();
        $kacab = $this->staff('kacab.jkt@sikredit.test');
        $this->actingAs($kacab)->post(route('admin.process.decide', $app), [
            'decision' => 'approve', 'approved_amount' => 45_000_000, 'approved_tenor' => 24, 'approved_rate' => 13.5, 'notes' => 'Disetujui dengan penyesuaian plafon.',
        ])->assertSessionHasNoErrors();
        $app->refresh();
        $this->assertSame(S::Approved, $app->status);
        $this->assertNotNull($app->sp3k_number);
        $this->assertEquals(45_000_000, $app->approved_amount);

        // 4. Legal: jadwal akad dan akad selesai
        $legal = $this->staff('legal.jkt@sikredit.test');
        $this->actingAs($legal)->post(route('admin.process.legal', $app), ['akad_scheduled_at' => now()->addDay()->format('Y-m-d H:i')])->assertSessionHasNoErrors();
        $this->actingAs($legal)->post(route('admin.process.contract', $app), ['akad_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(S::Contract, $app->refresh()->status);

        // 5. Teller mencairkan dana -> jadwal angsuran otomatis
        $teller = $this->staff('teller.jkt@sikredit.test');
        $this->actingAs($teller)->post(route('admin.process.disburse', $app), [
            'disbursed_at' => today()->toDateString(), 'disbursement_bank' => 'SiKredit Bank', 'disbursement_account' => '1234567890',
        ])->assertRedirect(route('admin.loans.show', $app));
        $app->refresh();
        $this->assertSame(S::Active, $app->status);
        $this->assertCount(24, $app->installments);
        $this->assertEqualsWithDelta(45_000_000, $app->installments->sum('principal'), 0.01);
        $this->assertEqualsWithDelta(45_000_000 * 0.98 - 500_000, $app->net_disbursed, 0.01); // provisi 2% + admin 500rb
        $this->assertNotSame('1234567890', \DB::table('loan_applications')->where('id', $app->id)->value('disbursement_account'), 'Nomor rekening harus terenkripsi');

        // 6. Bayar angsuran pertama
        $first = $app->installments->first();
        $this->actingAs($teller)->post(route('admin.loans.pay', $app), ['amount' => $first->amount, 'method' => 'tunai', 'paid_at' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame('paid', $first->refresh()->status);
        $this->assertLessThan(45_000_000, $app->refresh()->outstanding_principal);

        // 7. Pelunasan dipercepat -> status Lunas
        $this->actingAs($teller)->post(route('admin.loans.payoff', $app), ['method' => 'transfer', 'paid_at' => today()->toDateString(), 'confirm' => '1'])->assertSessionHasNoErrors();
        $app->refresh();
        $this->assertSame(S::PaidOff, $app->status);
        $this->assertEquals(0, $app->outstanding_principal);
        $this->assertSame(0, $app->installments()->where('status', '!=', 'paid')->count());

        // Riwayat status lengkap tercatat & nasabah dapat mengunduh dokumen cetak
        $this->assertSame(
            ['draft', 'submitted', 'analysis', 'approval', 'approved', 'contract', 'active', 'paid_off'],
            $app->logs()->reorder('id')->pluck('to_status')->map->value->all(),
        );
        $this->actingAs($customer)->get(route('print.schedule', $app).'?html=1')->assertOk();
        $this->actingAs($customer)->get(route('customer.loans.show', $app))->assertOk()->assertSee('Lunas');
    }

    public function test_large_amount_requires_committee_approval(): void
    {
        $app = $this->submittedApplication('KPR-01', 800_000_000, 120);
        $app->documents()->update(['status' => 'valid']);
        $analyst = $this->staff('analis.jkt@sikredit.test');
        $this->actingAs($this->staff('cs.jkt@sikredit.test'))->post(route('admin.process.verify', $app), ['analyst_id' => $analyst->id]);

        $this->actingAs($analyst)->post(route('admin.process.survey', $app), ['visit_date' => today()->toDateString()]);
        foreach ($app->collaterals as $c) {
            $this->actingAs($analyst)->post(route('admin.process.appraise', [$app, $c]), ['market_value' => 1.6e9, 'liquidation_value' => 1.2e9])->assertSessionHasNoErrors();
        }
        $this->actingAs($analyst)->post(route('admin.process.analyze', $app), [
            'character_score' => 90, 'capacity_score' => 85, 'capital_score' => 80, 'collateral_score' => 90, 'condition_score' => 85,
            'slik_collectibility' => 1, 'recommended_amount' => 800_000_000, 'recommended_tenor' => 120, 'recommended_rate' => 7.75, 'summary' => 'Layak.',
        ])->assertSessionHasNoErrors();
        $this->actingAs($analyst)->post(route('admin.process.submit-approval', $app), ['recommendation' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame(Role::Komite->value, $app->refresh()->required_approver_role);

        $payload = ['decision' => 'approve', 'approved_amount' => 800_000_000, 'approved_tenor' => 120, 'approved_rate' => 7.75, 'notes' => 'OK'];
        $this->actingAs($this->staff('kacab.jkt@sikredit.test'))->post(route('admin.process.decide', $app), $payload)->assertSessionHasErrors();
        $this->assertSame(S::Approval, $app->refresh()->status);

        $this->actingAs($this->staff('komite@sikredit.test'))->post(route('admin.process.decide', $app), $payload)->assertSessionHasNoErrors();
        $this->assertSame(S::Approved, $app->refresh()->status);
    }

    public function test_revision_flow_returns_application_to_customer(): void
    {
        $app = $this->submittedApplication();
        $doc = $app->documents->first();
        $cs = $this->staff('cs.jkt@sikredit.test');

        $this->actingAs($cs)->post(route('admin.process.document', [$app, $doc]), ['status' => 'invalid'])->assertSessionHasErrors('note');
        $this->actingAs($cs)->post(route('admin.process.document', [$app, $doc]), ['status' => 'invalid', 'note' => 'Buram'])->assertSessionHasNoErrors();
        $this->actingAs($cs)->post(route('admin.process.revision', $app), ['notes' => 'KTP buram'])->assertSessionHasNoErrors();
        $this->assertSame(S::Revision, $app->refresh()->status);

        $customer = $app->customer->user;
        $this->actingAs($customer)->post(route('customer.applications.submit', $app), ['agreement' => '1'])->assertSessionHasErrors('documents');
        $this->actingAs($customer)->post(route('customer.applications.documents.store', $app), [
            'document_type_id' => $doc->document_type_id, 'file' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertSessionHasNoErrors();
        $this->actingAs($customer)->post(route('customer.applications.submit', $app), ['agreement' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(S::Submitted, $app->refresh()->status);
    }

    public function test_authorization_boundaries(): void
    {
        $app = $this->submittedApplication();
        $doc = $app->documents->first();

        // Tamu diarahkan ke login
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));

        // Nasabah lain tidak bisa melihat pengajuan / dokumen orang lain
        $other = User::query()->create(['name' => 'Lain', 'email' => 'lain@contoh.test', 'password' => 'rahasia123', 'role' => Role::Nasabah]);
        $other->customer()->create();
        $this->actingAs($other)->get(route('customer.applications.show', $app))->assertForbidden();
        $this->actingAs($other)->get(route('documents.show', $doc))->assertForbidden();

        // Nasabah tidak bisa membuka area petugas
        $this->actingAs($app->customer->user)->get(route('admin.dashboard'))->assertForbidden();

        // Petugas cabang lain tidak bisa melihat pengajuan cabang Jakarta
        $this->actingAs($this->staff('cs.bdg@sikredit.test'))->get(route('admin.applications.show', $app))->assertForbidden();
        $this->actingAs($this->staff('cs.jkt@sikredit.test'))->get(route('admin.applications.show', $app))->assertOk();

        // Hanya admin yang dapat mengelola master data
        $this->actingAs($this->staff('kacab.jkt@sikredit.test'))->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($this->staff('admin@sikredit.test'))->get(route('admin.users.index'))->assertOk();

        // Teller tidak dapat memverifikasi dokumen
        $this->actingAs($this->staff('teller.jkt@sikredit.test'))->post(route('admin.process.document', [$app, $doc]), ['status' => 'valid'])->assertForbidden();
    }

    public function test_sensitive_customer_data_is_encrypted_at_rest(): void
    {
        $app = $this->submittedApplication();
        $raw = \DB::table('customers')->where('id', $app->customer_id)->value('nik');

        $this->assertNotSame('3171012345678901', $raw);
        $this->assertSame('3171012345678901', $app->customer->nik);
    }

    public function test_login_is_rate_limited_and_inactive_users_are_rejected(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['email' => 'admin@sikredit.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        }
        $this->post('/masuk', ['email' => 'admin@sikredit.test', 'password' => 'salah'])->assertStatus(429);

        $u = $this->staff('teller.bdg@sikredit.test');
        $u->update(['is_active' => false]);
        $this->post('/masuk', ['email' => $u->email, 'password' => UserSeeder::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
