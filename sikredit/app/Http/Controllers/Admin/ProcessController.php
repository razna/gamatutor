<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus as S;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\Collateral;
use App\Models\LoanApplication;
use App\Models\Setting;
use App\Notifications\GeneralNotification;
use App\Services\ApplicationWorkflow;
use App\Services\CreditScoringService;
use App\Services\LoanCalculator;
use App\Services\LoanService;
use App\Services\NumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Aksi pemrosesan pengajuan oleh petugas sesuai tahapan workflow. */
class ProcessController extends Controller
{
    public function __construct(private ApplicationWorkflow $workflow) {}

    private function guard(Request $request, LoanApplication $app, S $status): void
    {
        Gate::authorize('view-application', $app);
        if ($app->status !== $status) {
            throw ValidationException::withMessages(['status' => 'Aksi tidak valid: status pengajuan saat ini "'.$app->status->label().'".']);
        }
    }

    // ---- Customer Service -----------------------------------------------------------------

    public function verifyDocument(Request $request, LoanApplication $application, ApplicationDocument $document)
    {
        $this->guard($request, $application, S::Submitted);
        abort_unless($document->loan_application_id === $application->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['valid', 'invalid'])],
            'note' => ['nullable', 'required_if:status,invalid', 'string', 'max:255'],
        ], ['note.required_if' => 'Isi alasan bila dokumen tidak valid.']);

        $document->update($data + ['verified_by' => $request->user()->id, 'verified_at' => now()]);
        AuditLog::record('document.verify', $application, "Dokumen {$document->type->name}: {$data['status']}");

        return back()->with('success', 'Status dokumen diperbarui.');
    }

    public function verify(Request $request, LoanApplication $application)
    {
        $this->guard($request, $application, S::Submitted);
        $data = $request->validate([
            'analyst_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Analis->value)->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $application->load('documents', 'product.documentTypes');
        if ($application->documents->where('status', '!=', 'valid')->isNotEmpty() || $application->missingRequiredDocuments()->isNotEmpty()) {
            throw ValidationException::withMessages(['analyst_id' => 'Semua dokumen wajib harus berstatus Valid sebelum diteruskan ke analis.']);
        }

        $this->workflow->transition($application, S::Analysis, $request->user(), ($data['notes'] ?? null) ?: 'Dokumen lengkap dan valid. Diteruskan ke analis kredit.', [
            'cs_id' => $request->user()->id,
            'analyst_id' => $data['analyst_id'],
        ]);

        return redirect()->route('admin.applications.index')->with('success', 'Pengajuan diverifikasi dan diteruskan ke analis.');
    }

    public function revision(Request $request, LoanApplication $application)
    {
        Gate::authorize('view-application', $application);
        $data = $request->validate(['notes' => ['required', 'string', 'max:1000']]);
        $this->workflow->transition($application, S::Revision, $request->user(), $data['notes'], [
            'cs_id' => $application->cs_id ?? ($request->user()->hasRole(Role::CustomerService) ? $request->user()->id : null),
        ]);

        return redirect()->route('admin.applications.index')->with('success', 'Pengajuan dikembalikan ke nasabah untuk revisi.');
    }

    public function reject(Request $request, LoanApplication $application)
    {
        $this->guard($request, $application, S::Submitted);
        $data = $request->validate(['notes' => ['required', 'string', 'max:1000']]);
        $this->workflow->transition($application, S::Rejected, $request->user(), $data['notes'], [
            'cs_id' => $request->user()->id, 'decision_notes' => $data['notes'], 'decided_at' => now(),
        ]);

        return redirect()->route('admin.applications.index')->with('success', 'Pengajuan ditolak.');
    }

    // ---- Analis Kredit --------------------------------------------------------------------

    private function guardAnalyst(Request $request, LoanApplication $app): void
    {
        $this->guard($request, $app, S::Analysis);
        if ($app->analyst_id && $app->analyst_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'Pengajuan ini ditangani analis lain.');
        }
    }

    public function survey(Request $request, LoanApplication $application)
    {
        $this->guardAnalyst($request, $application);
        $data = $request->validate([
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'met_with' => ['nullable', 'string', 'max:100'],
            'address_verified' => ['boolean'],
            'income_verified' => ['boolean'],
            'business_verified' => ['boolean'],
            'residence_condition' => ['nullable', Rule::in(['baik', 'cukup', 'kurang'])],
            'neighborhood_info' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'max:4096'],
        ]);

        $photos = $application->survey?->photos ?? [];
        foreach ($request->file('photos', []) as $photo) {
            $photos[] = $photo->store('surveys/'.$application->id, 'local');
        }
        unset($data['photos']);

        $application->survey()->updateOrCreate([], $data + [
            'surveyor_id' => $request->user()->id,
            'photos' => array_slice($photos, -12),
            'address_verified' => $request->boolean('address_verified'),
            'income_verified' => $request->boolean('income_verified'),
            'business_verified' => $request->boolean('business_verified'),
        ]);
        AuditLog::record('analysis.survey', $application, 'Menyimpan hasil survei lapangan');

        return back()->with('success', 'Hasil survei disimpan.');
    }

    public function appraise(Request $request, LoanApplication $application, Collateral $collateral)
    {
        $this->guardAnalyst($request, $application);
        abort_unless($collateral->loan_application_id === $application->id, 404);
        $data = $request->validate([
            'market_value' => ['required', 'numeric', 'min:0'],
            'liquidation_value' => ['required', 'numeric', 'min:0', 'lte:market_value'],
            'binding_type' => ['nullable', Rule::in(array_keys(Collateral::BINDINGS))],
        ], ['liquidation_value.lte' => 'Nilai likuidasi tidak boleh melebihi nilai pasar.']);

        $collateral->update($data + ['appraised_by' => $request->user()->id, 'appraised_at' => today()]);
        AuditLog::record('analysis.appraisal', $application, "Appraisal agunan #{$collateral->id}: ".rupiah($data['market_value']));

        return back()->with('success', 'Penilaian agunan disimpan.');
    }

    public function analyze(Request $request, LoanApplication $application, CreditScoringService $scoring, LoanCalculator $calc)
    {
        $this->guardAnalyst($request, $application);
        $application->load('customer', 'collaterals', 'product');

        $rules = [
            'slik_collectibility' => ['required', 'integer', 'between:1,5'],
            'slik_notes' => ['nullable', 'string', 'max:2000'],
            'recommended_amount' => ['required', 'numeric', 'min:'.$application->product->min_amount, 'max:'.$application->product->max_amount],
            'recommended_tenor' => ['required', 'integer', 'min:'.$application->product->min_tenor, 'max:'.$application->product->max_tenor],
            'recommended_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'summary' => ['required', 'string', 'max:5000'],
        ];
        foreach (CreditScoringService::ASPECTS as $a) {
            $rules[$a.'_score'] = ['required', 'integer', 'between:0,100'];
            $rules[$a.'_notes'] = ['nullable', 'string', 'max:2000'];
        }
        $data = $request->validate($rules);

        $installment = $calc->summary((float) $data['recommended_amount'], (float) $data['recommended_rate'], (int) $data['recommended_tenor'], $application->interest_method)['max_installment'];
        $income = $application->customer->totalIncome();
        $dsr = $scoring->dsr($income, $application->customer->existing_installments, $installment);
        $ltv = $scoring->ltv((float) $data['recommended_amount'], $application->totalCollateralValue());

        $scores = [];
        foreach (CreditScoringService::ASPECTS as $a) {
            $scores[$a] = (int) $data[$a.'_score'];
        }
        $result = $scoring->score($scores, (int) $data['slik_collectibility'], $dsr, $application->product->requires_collateral ? $ltv : null, $application->product->max_ltv);

        $application->analysis()->updateOrCreate([], $data + [
            'analyst_id' => $request->user()->id,
            'net_income' => $income,
            'new_installment' => $installment,
            'dsr' => $dsr,
            'ltv' => $ltv,
            'total_score' => $result['total'],
            'grade' => $result['grade'],
            'recommendation' => $request->input('recommendation') === 'reject' ? 'reject' : $result['recommendation'],
        ]);
        if (! $application->analyst_id) {
            $application->update(['analyst_id' => $request->user()->id]);
        }
        AuditLog::record('analysis.score', $application, "Scoring {$result['total']} (grade {$result['grade']})", $result);

        $msg = "Analisis disimpan. Skor {$result['total']} — Grade {$result['grade']}.";
        if ($result['adjustments']) {
            $msg .= ' Catatan: '.implode(' ', $result['adjustments']);
        }

        return back()->with('success', $msg);
    }

    public function submitApproval(Request $request, LoanApplication $application)
    {
        $this->guardAnalyst($request, $application);
        $application->load('analysis', 'survey', 'collaterals', 'product');
        $data = $request->validate(['recommendation' => ['required', Rule::in(['approve', 'reject'])], 'notes' => ['nullable', 'string', 'max:1000']]);

        if (! $application->analysis) {
            throw ValidationException::withMessages(['recommendation' => 'Lengkapi analisis 5C dan scoring terlebih dahulu.']);
        }
        if (! $application->survey) {
            throw ValidationException::withMessages(['recommendation' => 'Lengkapi hasil survei lapangan terlebih dahulu.']);
        }
        if ($application->product->requires_collateral && $application->collaterals->whereNull('market_value')->isNotEmpty()) {
            throw ValidationException::withMessages(['recommendation' => 'Semua agunan harus dinilai (appraisal) terlebih dahulu.']);
        }

        $application->analysis->update(['recommendation' => $data['recommendation']]);
        $this->workflow->transition($application, S::Approval, $request->user(),
            'Rekomendasi analis: '.($data['recommendation'] === 'approve' ? 'SETUJU' : 'TOLAK').'. '.($data['notes'] ?? ''), [
                'analyst_id' => $application->analyst_id ?? $request->user()->id,
                'approved_amount' => $application->analysis->recommended_amount,
                'approved_tenor' => $application->analysis->recommended_tenor,
                'approved_rate' => $application->analysis->recommended_rate,
            ]);

        return redirect()->route('admin.applications.index')->with('success', 'Rekomendasi diteruskan ke pemutus kredit.');
    }

    // ---- Pemutus (Kepala Cabang / Komite) -------------------------------------------------

    public function decide(Request $request, LoanApplication $application)
    {
        $this->guard($request, $application, S::Approval);
        $application->load('product');
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'return'])],
            'approved_amount' => ['required_if:decision,approve', 'nullable', 'numeric', 'min:'.$application->product->min_amount, 'max:'.$application->product->max_amount],
            'approved_tenor' => ['required_if:decision,approve', 'nullable', 'integer', 'min:'.$application->product->min_tenor, 'max:'.$application->product->max_tenor],
            'approved_rate' => ['required_if:decision,approve', 'nullable', 'numeric', 'min:0', 'max:50'],
            'approval_conditions' => ['nullable', 'string', 'max:2000'],
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        // Prinsip four-eyes: pemutus tidak boleh petugas yang sama dengan pemroses sebelumnya.
        if (in_array($request->user()->id, [$application->analyst_id, $application->cs_id], true)) {
            abort(403, 'Pemisahan tugas: Anda tidak dapat memutus pengajuan yang Anda proses sendiri.');
        }

        if ($data['decision'] === 'return') {
            $this->workflow->transition($application, S::Analysis, $request->user(), 'Dikembalikan ke analis: '.$data['notes']);

            return redirect()->route('admin.applications.index')->with('success', 'Pengajuan dikembalikan ke analis.');
        }

        if ($data['decision'] === 'approve') {
            // Plafon yang dinaikkan di atas batas wewenang kepala cabang harus diputus komite.
            if ($request->user()->role === Role::KepalaCabang && (float) $data['approved_amount'] > (float) Setting::get('limit_kepala_cabang')) {
                throw ValidationException::withMessages(['approved_amount' => 'Plafon melebihi batas wewenang Kepala Cabang ('.rupiah(Setting::get('limit_kepala_cabang')).').']);
            }
            $this->workflow->transition($application, S::Approved, $request->user(), $data['notes'], [
                'approver_id' => $request->user()->id,
                'approved_amount' => $data['approved_amount'],
                'approved_tenor' => $data['approved_tenor'],
                'approved_rate' => $data['approved_rate'],
                'approval_conditions' => $data['approval_conditions'] ?? null,
                'decision_notes' => $data['notes'],
                'decided_at' => now(),
                'sp3k_number' => NumberGenerator::next('SP3K', 'loan_applications', 'sp3k_number'),
            ]);

            return redirect()->route('admin.applications.show', $application)->with('success', 'Kredit DISETUJUI. SP3K telah diterbitkan.');
        }

        $this->workflow->transition($application, S::Rejected, $request->user(), $data['notes'], [
            'approver_id' => $request->user()->id,
            'decision_notes' => $data['notes'],
            'decided_at' => now(),
        ]);

        return redirect()->route('admin.applications.index')->with('success', 'Kredit DITOLAK.');
    }

    // ---- Admin Legal ----------------------------------------------------------------------

    public function legal(Request $request, LoanApplication $application)
    {
        $this->guard($request, $application, S::Approved);
        $data = $request->validate([
            'akad_scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'notary_name' => ['nullable', 'string', 'max:150'],
            'binding_notes' => ['nullable', 'string', 'max:2000'],
            'bindings' => ['nullable', 'array'],
            'bindings.*.binding_type' => ['nullable', Rule::in(array_keys(Collateral::BINDINGS))],
            'bindings.*.binding_number' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($application, $data, $request) {
            $application->update([
                'legal_id' => $request->user()->id,
                'contract_number' => $application->contract_number ?? NumberGenerator::next('PK', 'loan_applications', 'contract_number'),
                'akad_scheduled_at' => $data['akad_scheduled_at'],
                'notary_name' => $data['notary_name'] ?? null,
                'binding_notes' => $data['binding_notes'] ?? null,
            ]);
            foreach ($data['bindings'] ?? [] as $id => $b) {
                $application->collaterals()->whereKey($id)->update(array_filter($b, fn ($v) => $v !== null));
            }
        });

        $application->customer->user->notify(new GeneralNotification(
            "Jadwal akad kredit {$application->number}",
            'Akad kredit Anda dijadwalkan pada '.tanggal($application->akad_scheduled_at, true).' di '.$application->branch->name.'. Harap membawa dokumen asli dan agunan.',
            route('customer.applications.show', $application),
            'calendar',
        ));
        AuditLog::record('legal.schedule', $application, 'Menjadwalkan akad dan menyiapkan perjanjian');

        return back()->with('success', 'Data legal disimpan dan nasabah telah diberi tahu jadwal akad.');
    }

    public function completeContract(Request $request, LoanApplication $application)
    {
        $this->guard($request, $application, S::Approved);
        $data = $request->validate(['akad_date' => ['required', 'date', 'before_or_equal:today'], 'notes' => ['nullable', 'string', 'max:1000']]);
        if (! $application->contract_number) {
            throw ValidationException::withMessages(['akad_date' => 'Siapkan perjanjian kredit dan jadwal akad terlebih dahulu.']);
        }
        if ($application->collaterals()->exists() && $application->collaterals()->whereNull('binding_number')->exists()) {
            throw ValidationException::withMessages(['akad_date' => 'Lengkapi nomor pengikatan seluruh agunan.']);
        }

        $this->workflow->transition($application, S::Contract, $request->user(), ($data['notes'] ?? null) ?: 'Akad kredit telah ditandatangani.', [
            'akad_date' => $data['akad_date'],
        ]);

        return redirect()->route('admin.applications.index')->with('success', 'Akad selesai. Pengajuan diteruskan ke bagian pencairan.');
    }

    // ---- Teller / Pencairan ---------------------------------------------------------------

    public function disburse(Request $request, LoanApplication $application, LoanService $loans)
    {
        $this->guard($request, $application, S::Contract);
        $data = $request->validate([
            'disbursed_at' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$application->akad_date?->toDateString()],
            'disbursement_bank' => ['required', 'string', 'max:100'],
            'disbursement_account' => ['required', 'regex:/^[0-9]{6,20}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['disbursement_account.regex' => 'Nomor rekening hanya angka (6–20 digit).']);

        $loans->disburse($application, $request->user(), $data);

        return redirect()->route('admin.loans.show', $application)->with('success', 'Dana berhasil dicairkan dan jadwal angsuran telah dibuat.');
    }
}
