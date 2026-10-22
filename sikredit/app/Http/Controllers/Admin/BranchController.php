<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin.master.branches', [
            'branches' => Branch::query()->withCount(['users', 'applications'])->orderBy('code')->get(),
            'editing' => request('edit') ? Branch::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $branch = Branch::query()->create($this->validated($request));
        AuditLog::record('master.branch.create', $branch, "Menambah cabang {$branch->name}");

        return back()->with('success', 'Cabang ditambahkan.');
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request, $branch));
        AuditLog::record('master.branch.update', $branch, "Mengubah cabang {$branch->name}");

        return redirect()->route('admin.branches.index')->with('success', 'Cabang diperbarui.');
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash', 'max:10', Rule::unique('branches', 'code')->ignore($branch)],
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
