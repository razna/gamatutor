<x-layouts.app title="Hari Libur" subtitle="Dipakai untuk perhitungan SLA dan penyesuaian tanggal jatuh tempo">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form class="flex gap-2 border-b border-slate-100 p-4"><input type="number" name="year" value="{{ $year }}" class="form-input w-32"><button class="btn-secondary">Tampilkan</button></form>
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Keterangan</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($holidays as $h)
                    <tr><td>{{ $h->date->locale('id')->translatedFormat('l, d F Y') }}</td><td>{{ $h->name }}</td>
                        <td class="text-right"><form method="POST" action="{{ route('admin.holidays.destroy', $h) }}">@csrf @method('DELETE')<button class="text-rose-600 hover:underline">Hapus</button></form></td></tr>
                @empty
                    <tr><td colspan="3" class="py-8 text-center text-slate-500">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('admin.holidays.store') }}" class="card card-body h-fit space-y-3">
            @csrf
            <div class="card-title">Tambah Hari Libur</div>
            <x-field label="Tanggal" name="date" type="date" required />
            <x-field label="Keterangan" name="name" required />
            <button class="btn-primary w-full">Simpan</button>
        </form>
    </div>
</x-layouts.app>
