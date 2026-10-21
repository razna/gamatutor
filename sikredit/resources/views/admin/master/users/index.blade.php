<x-layouts.app title="Pengguna & Role">
    <x-slot:actions><a href="{{ route('admin.users.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Tambah Petugas</a></x-slot:actions>
    <div class="card">
        <form class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <input name="q" value="{{ request('q') }}" placeholder="Nama / email" class="form-input max-w-xs">
            <select name="role" class="form-input max-w-xs"><option value="">Semua role</option>@foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected(request('role') === $r->value)>{{ $r->label() }}</option>@endforeach</select>
            <button class="btn-secondary">Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Cabang</th><th>Login terakhir</th><th>Status</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach ($users as $u)
                    <tr><td class="font-medium">{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->role->label() }}</td><td>{{ $u->branch?->name ?? ($u->isStaff() ? 'Kantor Pusat' : '-') }}</td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->locale('id')->diffForHumans() : '-' }}</td>
                        <td><span class="badge {{ $u->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td><a href="{{ route('admin.users.edit', $u) }}" class="text-brand-700 hover:underline">Ubah</a></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $users->links() }}</div>
    </div>
</x-layouts.app>
