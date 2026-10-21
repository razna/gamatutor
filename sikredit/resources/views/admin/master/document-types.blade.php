<x-layouts.app title="Jenis Dokumen">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            <table class="table">
                <thead><tr><th>Kode</th><th>Nama</th><th>Keterangan</th><th>Dipakai produk</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach ($types as $t)
                    <tr><td class="font-mono text-xs">{{ $t->code }}</td><td class="font-medium">{{ $t->name }}</td><td class="whitespace-normal">{{ $t->description }}</td><td>{{ $t->products_count }}</td>
                        <td><a href="{{ route('admin.document-types.index', ['edit' => $t->id]) }}" class="text-brand-700 hover:underline">Ubah</a></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @php $t = $editing; @endphp
        <form method="POST" action="{{ $t ? route('admin.document-types.update', $t) : route('admin.document-types.store') }}" class="card card-body h-fit space-y-3">
            @csrf @if ($t) @method('PUT') @endif
            <div class="card-title">{{ $t ? 'Ubah' : 'Tambah' }} Jenis Dokumen</div>
            <x-field label="Kode" name="code" :value="$t?->code" required />
            <x-field label="Nama" name="name" :value="$t?->name" required />
            <x-field label="Keterangan" name="description" :value="$t?->description" />
            <div class="flex gap-2">@if ($t)<a href="{{ route('admin.document-types.index') }}" class="btn-secondary flex-1">Batal</a>@endif<button class="btn-primary flex-1">Simpan</button></div>
        </form>
    </div>
</x-layouts.app>
