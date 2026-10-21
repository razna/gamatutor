<x-layouts.app :title="$u->exists ? 'Ubah Pengguna' : 'Tambah Pengguna'">
    <form method="POST" action="{{ $u->exists ? route('admin.users.update', $u) : route('admin.users.store') }}" class="card card-body grid max-w-3xl gap-4 md:grid-cols-2">
        @csrf @if ($u->exists) @method('PUT') @endif
        <x-field label="Nama" name="name" :value="$u->name" required />
        <x-field label="Email" name="email" type="email" :value="$u->email" required />
        <x-field label="Telepon" name="phone" :value="$u->phone" />
        <x-field label="Role" name="role" :value="$u->role?->value" :options="collect(\App\Enums\Role::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])" required />
        <x-field label="Cabang" name="branch_id" :value="$u->branch_id" :options="$branches->pluck('name', 'id')" hint="Kosongkan untuk kantor pusat (akses semua cabang)." />
        <x-field label="Kata sandi" name="password" type="password" :required="! $u->exists" :hint="$u->exists ? 'Kosongkan bila tidak diubah.' : 'Min. 8 karakter huruf & angka.'" />
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $u->is_active))> Akun aktif</label>
        <div class="md:col-span-2 flex justify-end gap-2"><a href="{{ route('admin.users.index') }}" class="btn-secondary">Batal</a><button class="btn-primary">Simpan</button></div>
    </form>
</x-layouts.app>
