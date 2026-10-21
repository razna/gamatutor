<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()->with('branch')
            ->when($request->input('role'), fn ($q, $r) => $q->where('role', $r))
            ->when($request->input('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->orderBy('role')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.master.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.master.users.form', ['u' => new User(['is_active' => true, 'role' => Role::CustomerService]), 'branches' => Branch::query()->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = User::query()->create($data + ['email_verified_at' => now()]);
        AuditLog::record('user.create', $user, "Membuat pengguna {$user->email} ({$user->role->label()})");

        return redirect()->route('admin.users.index')->with('success', 'Pengguna ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.master.users.form', ['u' => $user, 'branches' => Branch::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($user->id === $request->user()->id && (! $data['is_active'] || $data['role'] !== Role::Admin->value)) {
            return back()->withErrors(['role' => 'Anda tidak dapat menonaktifkan atau menurunkan peran akun Anda sendiri.']);
        }
        $user->update($data);
        AuditLog::record('user.update', $user, "Mengubah pengguna {$user->email}", collect($user->getChanges())->except('password', 'updated_at')->all());

        return redirect()->route('admin.users.index')->with('success', 'Pengguna diperbarui.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(Role::class)],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'password' => [$user ? 'nullable' : 'required', Password::min(8)->letters()->numbers()],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
