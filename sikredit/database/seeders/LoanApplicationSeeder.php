<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus as S;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CollectionNote;
use App\Models\CreditProduct;
use App\Models\Customer;
use App\Models\LoanApplication;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\CreditScoringService;
use App\Services\LoanCalculator;
use App\Services\LoanService;
use App\Services\NumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat nasabah dan 20 pengajuan dengan status beragam. Setiap pengajuan dijalankan melalui
 * workflow & service yang sebenarnya (dengan waktu disimulasikan) sehingga data konsisten.
 */
class LoanApplicationSeeder extends Seeder
{
    private ApplicationWorkflow $workflow;

    private LoanService $loans;

    private const STEPS = ['draft', 'submitted', 'analysis', 'approval', 'approved', 'contract', 'active'];

    public function run(): void
    {
        $this->workflow = app(ApplicationWorkflow::class);
        $this->loans = app(LoanService::class);
        Notification::fake(); // jangan kirim email/notifikasi massal saat seeding

        $customers = $this->customers();
        $p = CreditProduct::query()->pluck('id', 'code');
        $b = Branch::query()->pluck('id', 'code');

        // [nasabah, produk, cabang, plafon, tenor, tujuan, status akhir, hari lalu dibuat, opsi]
        $scenarios = [
            [0, 'KPR-01', 'JKT01', 450e6, 180, 'pembelian_rumah', 'active', 400, ['pay' => 'all']],
            [1, 'KKB-01', 'JKT01', 180e6, 48, 'kendaraan', 'active', 300, ['pay' => 'all_but', 'skip' => 1]],
            [2, 'KMK-01', 'BDG01', 250e6, 36, 'modal_kerja', 'active', 330, ['pay' => 'stop', 'stop_months' => 4]],
            [3, 'KTA-01', 'SBY01', 60e6, 24, 'pendidikan', 'active', 420, ['pay' => 'stop', 'stop_months' => 7, 'notes' => true]],
            [4, 'KUR-01', 'YOG01', 50e6, 12, 'modal_kerja', 'paid_off', 420, ['pay' => 'payoff']],
            [5, 'MULTI-01', 'JKT01', 150e6, 60, 'renovasi', 'active', 200, ['pay' => 'all']],
            [6, 'KI-01', 'SBY01', 1.2e9, 84, 'investasi', 'approval', 14, []],
            [7, 'KPR-01', 'BDG01', 350e6, 240, 'pembelian_rumah', 'approval', 10, []],
            [8, 'KKB-01', 'YOG01', 220e6, 36, 'kendaraan', 'approved', 9, []],
            [9, 'KMK-01', 'JKT01', 300e6, 24, 'modal_kerja', 'contract', 8, []],
            [10, 'KTA-01', 'JKT01', 40e6, 24, 'kesehatan', 'analysis', 5, ['partial' => true]],
            [11, 'MULTI-01', 'SBY01', 120e6, 48, 'renovasi', 'analysis', 4, []],
            [12, 'KUR-01', 'BDG01', 75e6, 36, 'modal_kerja', 'analysis', 3, ['partial' => true]],
            [13, 'KTA-01', 'YOG01', 80e6, 36, 'konsumtif', 'submitted', 3, []],
            [14, 'KKB-01', 'JKT01', 95e6, 36, 'kendaraan', 'submitted', 1, []],
            [0, 'KTA-01', 'JKT01', 30e6, 12, 'pendidikan', 'submitted', 0, []],
            [1, 'MULTI-01', 'JKT01', 75e6, 24, 'renovasi', 'revision', 6, []],
            [2, 'KTA-01', 'BDG01', 200e6, 60, 'konsumtif', 'rejected', 30, []],
            [3, 'KPR-01', 'SBY01', 500e6, 240, 'pembelian_rumah', 'cancelled', 20, []],
            [5, 'KMK-01', 'JKT01', 100e6, 24, 'modal_kerja', 'draft', 1, []],
        ];

        foreach ($scenarios as [$ci, $prod, $branch, $amount, $tenor, $purpose, $target, $daysAgo, $opt]) {
            Carbon::setTestNow(today()->subDays($daysAgo)->setTime(9, 0));
            $app = $this->draft($customers[$ci], CreditProduct::query()->find($p[$prod]), $b[$branch], $amount, $tenor, $purpose);
            $this->advance($app, $target, $opt);
            Carbon::setTestNow();
        }

        foreach (LoanApplication::query()->status(S::Active)->get() as $loan) {
            $this->loans->refresh($loan);
        }

        $this->tickets($customers);
        AuditLog::query()->update(['ip_address' => '127.0.0.1', 'user_agent' => 'Seeder']);
    }

    private function customers(): array
    {
        $data = [
            ['Budi Santoso', 'karyawan', 'PT Telekomunikasi Nusantara', 'Supervisor IT', 18500000, 'menikah', 'Jakarta Selatan'],
            ['Siti Nurhaliza', 'karyawan', 'PT Bank Sentosa', 'Manajer Keuangan', 22000000, 'menikah', 'Jakarta Timur'],
            ['Ahmad Fauzi', 'wiraswasta', 'CV Maju Bersama (Distributor Sembako)', 'Pemilik', 35000000, 'menikah', 'Bandung'],
            ['Dewi Lestari', 'karyawan', 'PT Garuda Logistik', 'Staf Administrasi', 9500000, 'lajang', 'Surabaya'],
            ['Wayan Sudarta', 'wiraswasta', 'Kerajinan Perak Wayan', 'Pemilik', 12000000, 'menikah', 'Yogyakarta'],
            ['Rizky Ramadhan', 'pns', 'Dinas Pendidikan DKI Jakarta', 'Guru Ahli Madya', 11000000, 'menikah', 'Depok'],
            ['Hendro Gunawan', 'wiraswasta', 'PT Gunawan Plastik Industri', 'Direktur', 95000000, 'menikah', 'Sidoarjo'],
            ['Maya Anggraini', 'profesional', 'Klinik Gigi Senyum Sehat', 'Dokter Gigi', 28000000, 'menikah', 'Bandung'],
            ['Taufik Hidayat', 'karyawan', 'PT Astra Otoparts', 'Kepala Seksi Produksi', 16000000, 'menikah', 'Sleman'],
            ['Lina Marlina', 'wiraswasta', 'Toko Bangunan Sumber Rejeki', 'Pemilik', 42000000, 'menikah', 'Tangerang'],
            ['Fajar Nugroho', 'karyawan', 'PT Unilever Indonesia', 'Sales Executive', 13500000, 'lajang', 'Bekasi'],
            ['Indah Permatasari', 'profesional', 'Kantor Notaris Indah, S.H., M.Kn.', 'Notaris', 30000000, 'menikah', 'Surabaya'],
            ['Joko Susilo', 'wiraswasta', 'Warung Makan Joko', 'Pemilik', 15000000, 'menikah', 'Cimahi'],
            ['Putri Handayani', 'karyawan', 'RS Panti Rapih', 'Perawat', 8500000, 'lajang', 'Yogyakarta'],
            ['Dimas Aditya', 'karyawan', 'PT Gojek Tokopedia', 'Software Engineer', 26000000, 'lajang', 'Jakarta Barat'],
        ];
        $result = [];
        foreach ($data as $i => [$name, $occ, $employer, $title, $income, $marital, $city]) {
            $email = strtolower(explode(' ', $name)[0]).($i + 1).'@contoh.test';
            $user = User::query()->create([
                'name' => $name, 'email' => $email, 'phone' => '0813'.str_pad((string) (21000000 + $i * 7919), 8, '0', STR_PAD_LEFT),
                'password' => UserSeeder::PASSWORD, 'role' => Role::Nasabah, 'email_verified_at' => now(),
            ]);
            $married = $marital === 'menikah';
            $result[] = Customer::query()->create([
                'user_id' => $user->id,
                'nik' => '3'.str_pad((string) (171000000000000 + $i * 7777777), 15, '0', STR_PAD_LEFT),
                'kk_number' => '3'.str_pad((string) (171000000500000 + $i * 3333331), 15, '0', STR_PAD_LEFT),
                'npwp' => sprintf('%02d.%03d.%03d.%d-%03d.000', 10 + $i, 100 + $i, 200 + $i, $i % 10, 300 + $i),
                'birth_place' => $city, 'birth_date' => now()->subYears(28 + $i % 20)->subDays($i * 37),
                'gender' => in_array($i, [1, 3, 7, 9, 11, 13]) ? 'P' : 'L', 'marital_status' => $marital, 'dependents' => $married ? 1 + $i % 3 : 0,
                'education' => ['S1', 'S2', 'SMA', 'D3'][$i % 4], 'mother_name' => ['Sumiati', 'Kartini', 'Suharti', 'Rohmah', 'Ni Made Sari'][$i % 5],
                'address' => 'Jl. '.['Melati', 'Kenanga', 'Mawar', 'Anggrek', 'Flamboyan'][$i % 5].' No. '.(10 + $i).', RT 0'.(1 + $i % 9).'/RW 0'.(1 + $i % 5),
                'city' => $city, 'postal_code' => (string) (10110 + $i * 1013), 'home_status' => ['milik_sendiri', 'keluarga', 'sewa'][$i % 3],
                'occupation_type' => $occ, 'employer_name' => $employer, 'job_title' => $title, 'work_years' => 2 + $i % 12,
                'business_field' => $occ === 'wiraswasta' ? 'Perdagangan' : null, 'office_address' => 'Jl. Raya Kantor No. '.(100 + $i).', '.$city,
                'monthly_income' => $income, 'other_income' => $i % 3 === 0 ? 2000000 : 0, 'monthly_expense' => round($income * 0.4),
                'existing_installments' => $i % 4 === 0 ? 1500000 : 0,
                'spouse_name' => $married ? ['Ani', 'Rudi', 'Yanti', 'Agus', 'Ketut'][$i % 5].' '.explode(' ', $name)[count(explode(' ', $name)) - 1] : null,
                'spouse_occupation' => $married ? 'Ibu Rumah Tangga' : null, 'spouse_income' => $married && $i % 2 ? 5000000 : 0,
                'emergency_name' => 'Keluarga '.$name, 'emergency_relation' => 'Saudara kandung', 'emergency_phone' => '0857'.str_pad((string) (11000000 + $i * 4421), 8, '0', STR_PAD_LEFT),
            ]);
        }

        return $result;
    }

    private function draft(Customer $customer, CreditProduct $product, int $branchId, float $amount, int $tenor, string $purpose): LoanApplication
    {
        $app = LoanApplication::query()->create([
            'number' => NumberGenerator::next('KRD', 'loan_applications', 'number'),
            'customer_id' => $customer->id, 'credit_product_id' => $product->id, 'branch_id' => $branchId,
            'amount' => $amount, 'tenor' => $tenor, 'purpose' => $purpose,
            'purpose_detail' => LoanApplication::PURPOSES[$purpose].' sesuai rencana yang telah disusun.',
            'interest_rate' => $product->interest_rate, 'interest_method' => $product->interest_method,
            'status' => S::Draft, 'status_changed_at' => now(),
        ]);
        $this->workflow->logCreated($app, $customer->user);

        if ($product->requires_collateral) {
            $isVehicle = $product->category === 'KKB';
            $app->collaterals()->create([
                'type' => $isVehicle ? 'kendaraan' : 'tanah_bangunan',
                'description' => $isVehicle ? 'Toyota Avanza 1.5 G CVT tahun 2024, warna hitam' : 'Rumah tinggal LT 150 m² / LB 110 m²',
                'ownership_number' => $isVehicle ? 'BPKB Q-'.random_int(1000000, 9999999) : 'SHM No. '.random_int(1000, 9999).'/'.$customer->city,
                'owner_name' => $customer->user->name,
                'location' => $customer->address.', '.$customer->city,
                'estimated_value' => round($amount * ($isVehicle ? 1.25 : 1.6), -6),
            ]);
        }

        foreach ($product->documentTypes as $type) {
            $path = "documents/{$app->id}/".strtolower($type->code).'.pdf';
            Storage::disk('local')->put($path, $this->dummyPdf($type->name.' - '.$customer->user->name));
            $app->documents()->create([
                'document_type_id' => $type->id, 'path' => $path, 'original_name' => strtolower($type->code).'.pdf', 'mime' => 'application/pdf', 'size' => 900,
            ]);
        }

        return $app->load('product', 'customer.user', 'collaterals');
    }

    private function advance(LoanApplication $app, string $target, array $opt): void
    {
        $tick = fn (int $hours) => Carbon::setTestNow(now()->addHours($hours));
        $staff = fn (Role $role) => User::query()->where('role', $role->value)
            ->where(fn ($q) => $q->where('branch_id', $app->branch_id)->orWhereNull('branch_id'))->first();
        $customerUser = $app->customer->user;

        if ($target === 'draft') {
            return;
        }
        if ($target === 'cancelled') {
            $this->workflow->transition($app, S::Submitted, $customerUser, 'Pengajuan dikirim oleh nasabah.');
            $tick(20);
            $this->workflow->transition($app, S::Cancelled, $customerUser, 'Dibatalkan nasabah: memilih menunda pembelian rumah.');

            return;
        }

        $this->workflow->transition($app, S::Submitted, $customerUser, 'Pengajuan dikirim oleh nasabah.');
        if ($target === 'submitted') {
            return;
        }

        $cs = $staff(Role::CustomerService);
        $tick(6);
        if ($target === 'revision') {
            $doc = $app->documents()->first();
            $doc->update(['status' => 'invalid', 'note' => 'Foto buram, mohon unggah ulang dengan resolusi lebih jelas.', 'verified_by' => $cs->id, 'verified_at' => now()]);
            $this->workflow->transition($app, S::Revision, $cs, 'Dokumen '.$doc->type->name.' tidak terbaca. Mohon unggah ulang.', ['cs_id' => $cs->id]);

            return;
        }
        $app->documents()->update(['status' => 'valid', 'verified_by' => $cs->id, 'verified_at' => now()]);
        $analyst = $staff(Role::Analis);
        $this->workflow->transition($app, S::Analysis, $cs, 'Dokumen lengkap dan valid.', ['cs_id' => $cs->id, 'analyst_id' => $analyst->id]);
        if ($target === 'analysis' && empty($opt['partial'])) {
            return;
        }

        // Survei & appraisal
        $tick(30);
        $app->survey()->create([
            'surveyor_id' => $analyst->id, 'visit_date' => today(), 'met_with' => 'Pemohon dan pasangan',
            'address_verified' => true, 'income_verified' => true, 'business_verified' => true, 'residence_condition' => 'baik',
            'neighborhood_info' => 'Lingkungan perumahan tertata, akses jalan baik. Tetangga mengonfirmasi pemohon tinggal > 3 tahun.',
            'notes' => 'Kondisi sesuai data pengajuan.', 'latitude' => -6.2 - random_int(0, 900) / 10000, 'longitude' => 106.8 + random_int(0, 900) / 10000,
        ]);
        foreach ($app->collaterals as $c) {
            $c->update(['market_value' => $c->estimated_value * 0.95, 'liquidation_value' => $c->estimated_value * 0.7, 'appraised_by' => $analyst->id, 'appraised_at' => today(),
                'binding_type' => $c->type === 'kendaraan' ? 'FIDUSIA' : 'HT']);
        }
        if ($target === 'analysis') {
            return;
        }

        // Analisis & scoring
        $tick(20);
        $reject = $target === 'rejected';
        $this->analyze($app->refresh()->load('customer', 'collaterals', 'product'), $analyst, $reject);
        $this->workflow->transition($app, S::Approval, $analyst, 'Rekomendasi analis: '.($reject ? 'TOLAK' : 'SETUJU').'.', [
            'approved_amount' => $app->analysis->recommended_amount, 'approved_tenor' => $app->analysis->recommended_tenor, 'approved_rate' => $app->analysis->recommended_rate,
        ]);
        if ($target === 'approval') {
            return;
        }

        $tick(18);
        $approver = $app->requiredApproverRole() === Role::Komite ? User::query()->where('role', Role::Komite->value)->first() : $staff(Role::KepalaCabang);
        if ($reject) {
            $this->workflow->transition($app, S::Rejected, $approver, 'DSR melebihi batas kebijakan dan riwayat SLIK kurang baik.', [
                'approver_id' => $approver->id, 'decision_notes' => 'DSR melebihi batas kebijakan dan riwayat SLIK kurang baik.', 'decided_at' => now(),
            ]);

            return;
        }
        $this->workflow->transition($app, S::Approved, $approver, 'Disetujui sesuai rekomendasi analis.', [
            'approver_id' => $approver->id, 'decision_notes' => 'Disetujui sesuai rekomendasi analis.', 'decided_at' => now(),
            'approval_conditions' => $app->product->requires_collateral ? 'Agunan diasuransikan; pembayaran melalui autodebet.' : 'Pembayaran melalui autodebet rekening gaji.',
            'sp3k_number' => NumberGenerator::next('SP3K', 'loan_applications', 'sp3k_number'),
        ]);
        if ($target === 'approved') {
            return;
        }

        $tick(24);
        $legal = $staff(Role::Legal);
        $app->update(['legal_id' => $legal->id, 'contract_number' => NumberGenerator::next('PK', 'loan_applications', 'contract_number'),
            'akad_scheduled_at' => now()->addDay()->setTime(10, 0), 'notary_name' => 'Notaris Rahmat Hidayat, S.H., M.Kn.']);
        foreach ($app->collaterals as $c) {
            $c->update(['binding_number' => ($c->binding_type ?? 'HT').'-'.random_int(10000, 99999).'/'.now()->year]);
        }
        $tick(26);
        $this->workflow->transition($app, S::Contract, $legal, 'Akad kredit telah ditandatangani.', ['akad_date' => today()]);
        if ($target === 'contract') {
            return;
        }

        $tick(4);
        $teller = $staff(Role::Teller);
        $this->loans->disburse($app->refresh(), $teller, [
            'disbursed_at' => today()->toDateString(), 'disbursement_bank' => 'SiKredit Bank', 'disbursement_account' => (string) random_int(1000000000, 9999999999),
        ]);
        $this->payInstallments($app->refresh(), $teller, $opt);

        if (! empty($opt['notes'])) {
            $col = $staff(Role::Collection);
            foreach (['telepon' => 'Debitur tidak mengangkat telepon.', 'kunjungan' => 'Debitur ditemui di rumah, menyampaikan sedang kesulitan keuangan karena PHK.', 'sp1' => 'Surat Peringatan 1 dikirim ke alamat debitur.'] as $action => $result) {
                CollectionNote::query()->create(['loan_application_id' => $app->id, 'user_id' => $col->id, 'action' => $action, 'result' => $result,
                    'created_at' => Carbon::parse(today())->subDays(30), 'updated_at' => now()]);
            }
            CollectionNote::query()->create(['loan_application_id' => $app->id, 'user_id' => $col->id, 'action' => 'whatsapp',
                'result' => 'Debitur berjanji membayar sebagian tunggakan.', 'promise_date' => Carbon::today()->addDays(3), 'promise_amount' => 3000000]);
        }
    }

    private function analyze(LoanApplication $app, User $analyst, bool $weak): void
    {
        $scoring = app(CreditScoringService::class);
        $calc = app(LoanCalculator::class);
        $scores = $weak
            ? ['character' => 55, 'capacity' => 45, 'capital' => 50, 'collateral' => 50, 'condition' => 60]
            : ['character' => 85, 'capacity' => 80, 'capital' => 75, 'collateral' => 85, 'condition' => 80];
        $inst = $calc->summary($app->amount, $app->interest_rate, $app->tenor, $app->interest_method)['max_installment'];
        $income = $app->customer->totalIncome();
        $dsr = $scoring->dsr($income, $app->customer->existing_installments, $inst);
        $ltv = $scoring->ltv($app->amount, $app->totalCollateralValue());
        $kol = $weak ? 2 : 1;
        $r = $scoring->score($scores, $kol, $dsr, $app->product->requires_collateral ? $ltv : null, $app->product->max_ltv);

        $data = ['analyst_id' => $analyst->id, 'slik_collectibility' => $kol, 'slik_notes' => $weak ? 'Terdapat tunggakan 45 hari pada kartu kredit bank lain.' : 'Seluruh fasilitas lancar.',
            'net_income' => $income, 'new_installment' => $inst, 'dsr' => $dsr, 'ltv' => $ltv, 'total_score' => $r['total'], 'grade' => $r['grade'],
            'recommendation' => $weak ? 'reject' : 'approve', 'recommended_amount' => $app->amount, 'recommended_tenor' => $app->tenor, 'recommended_rate' => $app->interest_rate,
            'summary' => $weak ? 'Kemampuan bayar terbatas dan terdapat riwayat tunggakan. Tidak direkomendasikan.' : 'Pemohon memiliki penghasilan stabil, karakter baik, dan agunan memadai. Direkomendasikan untuk disetujui.'];
        foreach ($scores as $k => $v) {
            $data[$k.'_score'] = $v;
            $data[$k.'_notes'] = $weak ? 'Di bawah standar.' : 'Memenuhi kriteria.';
        }
        $app->analysis()->create($data);
    }

    private function payInstallments(LoanApplication $app, User $teller, array $opt): void
    {
        $start = Carbon::parse($app->disbursed_at);
        $today = Carbon::today();
        Carbon::setTestNow(); // gunakan tanggal riil untuk menentukan angsuran yang sudah jatuh tempo
        $due = $app->installments()->whereDate('due_date', '<=', today())->orderBy('number')->get();

        if (($opt['pay'] ?? null) === 'payoff') {
            foreach ($due->take(5) as $inst) {
                Carbon::setTestNow($inst->due_date->copy()->setTime(10, 0));
                $this->loans->recordPayment($app, $inst->amount, $teller, ['paid_at' => $inst->due_date->toDateString(), 'method' => 'autodebet']);
            }
            $payoffDate = $due->get(5)?->due_date->copy()->subDays(3) ?? $start->copy()->addMonths(6);
            Carbon::setTestNow($payoffDate->copy()->setTime(10, 0));
            $this->loans->payoff($app->refresh(), $teller, ['paid_at' => $payoffDate->toDateString(), 'method' => 'transfer', 'reference' => 'TRF'.random_int(100000, 999999)]);

            return;
        }

        $count = match ($opt['pay'] ?? 'all') {
            'all_but' => max(0, $due->count() - ($opt['skip'] ?? 1)),
            'stop' => max(0, $due->count() - ($opt['stop_months'] ?? 3)),
            default => $due->count(),
        };
        foreach ($due->take($count) as $inst) {
            $paidAt = $inst->due_date->copy()->subDays(random_int(0, 2));
            Carbon::setTestNow($paidAt->copy()->setTime(10, 0));
            $this->loans->recordPayment($app->refresh(), $inst->amount, $teller, ['paid_at' => $paidAt->toDateString(), 'method' => ['autodebet', 'tunai', 'transfer'][$inst->number % 3]]);
        }
        unset($today);
    }

    private function tickets(array $customers): void
    {
        $cs = User::query()->where('role', Role::CustomerService->value)->first();
        $t = Ticket::query()->create(['number' => NumberGenerator::next('TKT', 'tickets', 'number'), 'user_id' => $customers[1]->user_id,
            'category' => 'angsuran', 'subject' => 'Tanggal autodebet tidak sesuai', 'status' => 'answered']);
        $t->messages()->create(['user_id' => $customers[1]->user_id, 'message' => 'Angsuran bulan ini terdebet 2 hari lebih awal dari jatuh tempo. Mohon penjelasannya.']);
        $t->messages()->create(['user_id' => $cs->id, 'message' => 'Terima kasih atas laporannya. Jatuh tempo yang bertepatan dengan hari libur digeser ke hari kerja terdekat sesuai ketentuan. Kami telah memastikan tidak ada pendebetan ganda.']);

        $t2 = Ticket::query()->create(['number' => NumberGenerator::next('TKT', 'tickets', 'number'), 'user_id' => $customers[13]->user_id,
            'category' => 'pengajuan', 'subject' => 'Berapa lama proses verifikasi dokumen?', 'status' => 'open']);
        $t2->messages()->create(['user_id' => $customers[13]->user_id, 'message' => 'Saya sudah mengirim pengajuan KTA 3 hari lalu, kapan akan diverifikasi?']);
    }

    private function dummyPdf(string $text): string
    {
        $text = preg_replace('/[^A-Za-z0-9 .,\-]/', '', $text);
        $stream = "BT /F1 18 Tf 60 760 Td (CONTOH DOKUMEN) Tj 0 -30 Td /F1 12 Tf ({$text}) Tj 0 -20 Td (Dokumen dummy untuk keperluan demo.) Tj ET";

        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            .'4 0 obj<</Length '.strlen($stream).">>stream\n{$stream}\nendstream endobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
    }
}
