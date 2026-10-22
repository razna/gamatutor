<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InterestMethod;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CreditProduct;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = CreditProduct::query()->withCount('applications')->orderBy('category')->get();

        return view('admin.master.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.master.products.form', ['product' => new CreditProduct(['is_active' => true, 'requires_collateral' => true, 'max_ltv' => 80, 'penalty_rate' => 0.1, 'provision_fee' => 1]), 'documentTypes' => DocumentType::query()->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $product = DB::transaction(function () use ($request) {
            $product = CreditProduct::query()->create($this->validated($request));
            $this->syncDocs($product, $request);

            return $product;
        });
        AuditLog::record('master.product.create', $product, "Menambah produk {$product->name}");

        return redirect()->route('admin.products.index')->with('success', 'Produk kredit ditambahkan.');
    }

    public function edit(CreditProduct $product)
    {
        return view('admin.master.products.form', ['product' => $product->load('documentTypes'), 'documentTypes' => DocumentType::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, CreditProduct $product)
    {
        $data = $this->validated($request, $product);
        DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            $this->syncDocs($product, $request);
        });
        AuditLog::record('master.product.update', $product, "Mengubah produk {$product->name}", $product->getChanges());

        return redirect()->route('admin.products.index')->with('success', 'Produk kredit diperbarui.');
    }

    public function destroy(CreditProduct $product)
    {
        if ($product->applications()->exists()) {
            $product->update(['is_active' => false]);

            return back()->with('warning', 'Produk sudah memiliki pengajuan sehingga hanya dinonaktifkan.');
        }
        $product->delete();
        AuditLog::record('master.product.delete', null, "Menghapus produk {$product->name}");

        return back()->with('success', 'Produk dihapus.');
    }

    private function validated(Request $request, ?CreditProduct $product = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('credit_products', 'code')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(CreditProduct::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'requirements' => ['nullable', 'string', 'max:3000'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'interest_method' => ['required', Rule::enum(InterestMethod::class)],
            'min_amount' => ['required', 'numeric', 'min:100000'],
            'max_amount' => ['required', 'numeric', 'gt:min_amount'],
            'min_tenor' => ['required', 'integer', 'min:1'],
            'max_tenor' => ['required', 'integer', 'gte:min_tenor', 'max:360'],
            'provision_fee' => ['required', 'numeric', 'min:0', 'max:10'],
            'admin_fee' => ['required', 'numeric', 'min:0'],
            'penalty_rate' => ['required', 'numeric', 'min:0', 'max:5'],
            'max_ltv' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $data['requires_collateral'] = $request->boolean('requires_collateral');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function syncDocs(CreditProduct $product, Request $request): void
    {
        $sync = [];
        foreach ((array) $request->input('documents', []) as $id => $mode) {
            if (in_array($mode, ['required', 'optional'], true)) {
                $sync[(int) $id] = ['is_required' => $mode === 'required'];
            }
        }
        $product->documentTypes()->sync($sync);
    }
}
