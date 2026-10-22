<x-layouts.app title="Produk Kredit">
    <x-slot:actions><a href="{{ route('admin.products.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Tambah Produk</a></x-slot:actions>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Bunga</th><th>Plafon</th><th>Tenor</th><th>Pengajuan</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach ($products as $p)
                <tr>
                    <td class="font-mono text-xs">{{ $p->code }}</td><td class="font-medium">{{ $p->name }}</td><td>{{ $p->category }}</td>
                    <td>{{ $p->interest_rate }}% {{ $p->interest_method->label() }}</td><td>{{ rupiah($p->min_amount) }} – {{ rupiah($p->max_amount) }}</td><td>{{ $p->min_tenor }}–{{ $p->max_tenor }}</td>
                    <td>{{ $p->applications_count }}</td>
                    <td><span class="badge {{ $p->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-right">
                        <a href="{{ route('admin.products.edit', $p) }}" class="text-brand-700 hover:underline">Ubah</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $p) }}" class="inline" onsubmit="return confirm('Hapus/nonaktifkan produk ini?')">@csrf @method('DELETE')<button class="ml-2 text-rose-600 hover:underline">Hapus</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
