<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ApplicationStatus as S;
use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Collateral;
use App\Models\CreditProduct;
use App\Models\LoanApplication;
use App\Services\ApplicationWorkflow;
use App\Services\LoanCalculator;
use App\Services\NumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    public function __construct(private ApplicationWorkflow $workflow) {}

    private function own(Request $request, LoanApplication $application): LoanApplication
    {
        abort_unless($application->customer->user_id === $request->user()->id, 403);

        return $application;
    }

    public function index(Request $request)
    {
        $applications = $request->user()->customer->applications()
            ->with('product', 'branch')
            ->filter($request->only('status', 'q'))
            ->latest()->paginate(10)->withQueryString();

        return view('customer.applications.index', compact('applications'));
    }

    public function create(Request $request)
    {
        $customer = $request->user()->customer;
        if (! $customer->isComplete()) {
            return redirect()->route('customer.profile.edit')->with('warning', 'Lengkapi data profil (NIK, data diri, pekerjaan, penghasilan, kontak darurat) sebelum mengajukan kredit.');
        }

        return view('customer.applications.form', [
            'application' => new LoanApplication(['credit_product_id' => $request->integer('product') ?: null]),
            'products' => CreditProduct::query()->active()->with('documentTypes')->get(),
            'branches' => Branch::query()->active()->orderBy('name')->get(),
            'customer' => $customer,
        ]);
    }

    public function store(Request $request)
    {
        $customer = $request->user()->customer;
        abort_unless($customer->isComplete(), 422, 'Profil belum lengkap.');
        $data = $this->validated($request);

        $application = DB::transaction(function () use ($data, $customer, $request) {
            $product = CreditProduct::query()->findOrFail($data['credit_product_id']);
            $app = LoanApplication::query()->create([
                'number' => NumberGenerator::next('KRD', 'loan_applications', 'number'),
                'customer_id' => $customer->id,
                'credit_product_id' => $product->id,
                'branch_id' => $data['branch_id'],
                'amount' => $data['amount'],
                'tenor' => $data['tenor'],
                'purpose' => $data['purpose'],
                'purpose_detail' => $data['purpose_detail'] ?? null,
                'interest_rate' => $product->interest_rate,
                'interest_method' => $product->interest_method,
                'status' => S::Draft,
                'status_changed_at' => now(),
            ]);
            $this->syncCollaterals($app, $data['collaterals'] ?? []);
            $this->workflow->logCreated($app, $request->user());
            AuditLog::record('application.create', $app, "Membuat draft pengajuan {$app->number}");

            return $app;
        });

        return redirect()->route('customer.applications.show', $application)
            ->with('success', 'Draft pengajuan tersimpan. Silakan unggah dokumen persyaratan, lalu kirim pengajuan.');
    }

    public function show(Request $request, LoanApplication $application, LoanCalculator $calc)
    {
        $this->own($request, $application)->load('product.documentTypes', 'branch', 'documents.type', 'collaterals', 'logs.user', 'analysis');
        $simulation = $calc->summary($application->finalAmount(), $application->finalRate(), $application->finalTenor(), $application->interest_method);

        return view('customer.applications.show', compact('application', 'simulation'));
    }

    public function edit(Request $request, LoanApplication $application)
    {
        $this->own($request, $application);
        abort_unless($application->status->isEditableByCustomer(), 403, 'Pengajuan tidak dapat diubah pada status ini.');
        $application->load('collaterals');

        return view('customer.applications.form', [
            'application' => $application,
            'products' => CreditProduct::query()->active()->with('documentTypes')->get(),
            'branches' => Branch::query()->active()->orderBy('name')->get(),
            'customer' => $request->user()->customer,
        ]);
    }

    public function update(Request $request, LoanApplication $application)
    {
        $this->own($request, $application);
        abort_unless($application->status->isEditableByCustomer(), 403);
        $data = $this->validated($request);
        $product = CreditProduct::query()->findOrFail($data['credit_product_id']);

        DB::transaction(function () use ($application, $data, $product) {
            $application->update([
                'credit_product_id' => $product->id,
                'branch_id' => $data['branch_id'],
                'amount' => $data['amount'],
                'tenor' => $data['tenor'],
                'purpose' => $data['purpose'],
                'purpose_detail' => $data['purpose_detail'] ?? null,
                'interest_rate' => $product->interest_rate,
                'interest_method' => $product->interest_method,
            ]);
            $this->syncCollaterals($application, $data['collaterals'] ?? []);
        });
        AuditLog::record('application.update', $application, "Memperbarui pengajuan {$application->number}");

        return redirect()->route('customer.applications.show', $application)->with('success', 'Pengajuan berhasil diperbarui.');
    }

    public function submit(Request $request, LoanApplication $application)
    {
        $this->own($request, $application)->load('product.documentTypes', 'documents', 'collaterals');
        $missing = $application->missingRequiredDocuments();
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['documents' => 'Dokumen wajib belum lengkap: '.$missing->pluck('name')->join(', ').'.']);
        }
        if ($application->product->requires_collateral && $application->collaterals->isEmpty()) {
            throw ValidationException::withMessages(['documents' => 'Produk ini mensyaratkan agunan. Tambahkan data agunan terlebih dahulu.']);
        }
        $request->validate(['agreement' => ['accepted']], ['agreement.accepted' => 'Anda harus menyetujui pernyataan kebenaran data dan persetujuan pemeriksaan SLIK.']);

        // Dokumen yang ditolak saat revisi harus sudah diganti.
        if ($application->documents->contains('status', 'invalid')) {
            throw ValidationException::withMessages(['documents' => 'Masih ada dokumen berstatus "Tidak Valid". Hapus dan unggah ulang dokumen tersebut.']);
        }

        $this->workflow->transition($application, S::Submitted, $request->user(), $application->status === S::Revision ? 'Pengajuan diperbaiki dan dikirim ulang.' : 'Pengajuan dikirim oleh nasabah.');

        return back()->with('success', 'Pengajuan berhasil dikirim dan akan diverifikasi oleh Customer Service.');
    }

    public function cancel(Request $request, LoanApplication $application)
    {
        $this->own($request, $application);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->workflow->transition($application, S::Cancelled, $request->user(), 'Dibatalkan nasabah: '.$data['reason']);

        return redirect()->route('customer.applications.index')->with('success', 'Pengajuan dibatalkan.');
    }

    public function uploadDocument(Request $request, LoanApplication $application)
    {
        $this->own($request, $application);
        abort_unless($application->status->isEditableByCustomer(), 403, 'Dokumen tidak dapat diubah pada status ini.');

        $data = $request->validate([
            'document_type_id' => ['required', Rule::exists('credit_product_document_type', 'document_type_id')->where('credit_product_id', $application->credit_product_id)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/'.$application->id, 'local'); // penyimpanan privat

        $existing = $application->documents()->where('document_type_id', $data['document_type_id'])->first();
        if ($existing) {
            Storage::disk('local')->delete($existing->path);
            $existing->delete();
        }

        $application->documents()->create([
            'document_type_id' => $data['document_type_id'],
            'path' => $path,
            'original_name' => substr($file->getClientOriginalName(), 0, 200),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
        AuditLog::record('document.upload', $application, 'Unggah dokumen '.$file->getClientOriginalName());

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function deleteDocument(Request $request, LoanApplication $application, ApplicationDocument $document)
    {
        $this->own($request, $application);
        abort_unless($document->loan_application_id === $application->id && $application->status->isEditableByCustomer(), 403);
        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }

    private function validated(Request $request): array
    {
        $product = CreditProduct::query()->active()->find($request->input('credit_product_id'));

        return $request->validate([
            'credit_product_id' => ['required', Rule::exists('credit_products', 'id')->where('is_active', true)],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'amount' => ['required', 'numeric', 'min:'.($product->min_amount ?? 1000000), 'max:'.($product->max_amount ?? 100000000000)],
            'tenor' => ['required', 'integer', 'min:'.($product->min_tenor ?? 1), 'max:'.($product->max_tenor ?? 360)],
            'purpose' => ['required', Rule::in(array_keys(LoanApplication::PURPOSES))],
            'purpose_detail' => ['nullable', 'string', 'max:1000'],
            'collaterals' => [$product?->requires_collateral ? 'required' : 'nullable', 'array', 'max:5'],
            'collaterals.*.type' => ['required', Rule::in(array_keys(Collateral::TYPES))],
            'collaterals.*.description' => ['required', 'string', 'max:255'],
            'collaterals.*.ownership_number' => ['nullable', 'string', 'max:100'],
            'collaterals.*.owner_name' => ['nullable', 'string', 'max:100'],
            'collaterals.*.location' => ['nullable', 'string', 'max:500'],
            'collaterals.*.estimated_value' => ['required', 'numeric', 'min:0'],
        ], [
            'collaterals.required' => 'Produk ini mensyaratkan minimal satu agunan.',
            'amount.min' => 'Plafon minimal untuk produk ini adalah '.rupiah($product->min_amount ?? 0).'.',
            'amount.max' => 'Plafon maksimal untuk produk ini adalah '.rupiah($product->max_amount ?? 0).'.',
        ]);
    }

    private function syncCollaterals(LoanApplication $app, array $items): void
    {
        $app->collaterals()->delete();
        foreach ($items as $c) {
            $app->collaterals()->create([
                'type' => $c['type'],
                'description' => $c['description'],
                'ownership_number' => $c['ownership_number'] ?? null,
                'owner_name' => $c['owner_name'] ?? null,
                'location' => $c['location'] ?? null,
                'estimated_value' => $c['estimated_value'],
            ]);
        }
    }
}
