<x-layouts.app title="Cabang">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            <table class="table">
                <thead><tr><th>Kode</th><th>Nama</th><th>Kota</th><th>Telepon</th><th>Petugas</th><th>Pengajuan</th><th>Status</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach ($branches as $b)
                    <tr><td class="font-mono text-xs">{{ $b->code }}</td><td class="font-medium">{{ $b->name }}</td><td>{{ $b->city }}</td><td>{{ $b->phone }}</td><td>{{ $b->users_count }}</td><td>{{ $b->applications_count }}</td>
                        <td><span class="badge {{ $b->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">{{ $b->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td><a href="{{ route('admin.branches.index', ['edit' => $b->id]) }}" class="text-brand-700 hover:underline">Ubah</a></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @php $b = $editing; @endphp
        <form method="POST" action="{{ $b ? route('admin.branches.update', $b) : route('admin.branches.store') }}" class="card card-body h-fit space-y-3">
            @csrf @if ($b) @method('PUT') @endif
            <div class="card-title">{{ $b ? 'Ubah Cabang' : 'Tambah Cabang' }}</div>
            <x-field label="Kode" name="code" :value="$b?->code" required />
            <x-field label="Nama cabang" name="name" :value="$b?->name" required />
            <x-field label="Alamat" name="address" :value="$b?->address" />
            <x-field label="Kota" name="city" :value="$b?->city" />
            <x-field label="Telepon" name="phone" :value="$b?->phone" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked($b?->is_active ?? true)> Aktif</label>
            <div class="flex gap-2">@if ($b)<a href="{{ route('admin.branches.index') }}" class="btn-secondary flex-1">Batal</a>@endif<button class="btn-primary flex-1">Simpan</button></div>
        </form>
    </div>
</x-layouts.app>
