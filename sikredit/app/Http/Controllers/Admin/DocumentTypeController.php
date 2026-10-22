<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTypeController extends Controller
{
    public function index()
    {
        return view('admin.master.document-types', [
            'types' => DocumentType::query()->withCount('products')->orderBy('name')->get(),
            'editing' => request('edit') ? DocumentType::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        DocumentType::query()->create($this->validated($request));

        return back()->with('success', 'Jenis dokumen ditambahkan.');
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $documentType->update($this->validated($request, $documentType));

        return redirect()->route('admin.document-types.index')->with('success', 'Jenis dokumen diperbarui.');
    }

    private function validated(Request $request, ?DocumentType $type = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('document_types', 'code')->ignore($type)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
