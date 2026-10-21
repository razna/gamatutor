<x-layouts.app title="Pengajuan Saya">
    <x-slot:actions><a href="{{ route('customer.applications.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Ajukan Kredit</a></x-slot:actions>
    <div class="card">
        <form class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <input name="q" value="{{ request('q') }}" placeholder="Cari nomor pengajuan…" class="form-input max-w-xs">
            <select name="status" class="form-input max-w-xs">
                <option value="">Semua status</option>
                @foreach (\App\Enums\ApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach
            </select>
            <button class="btn-secondary">Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Pengajuan</th><th>Produk</th><th class="text-right">Plafon</th><th>Tenor</th><th>Cabang</th><th>Status</th><th>Dibuat</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($applications as $a)
                    <tr>
                        <td class="font-medium">{{ $a->number }}</td>
                        <td>{{ $a->product->name }}</td>
                        <td class="text-right">{{ rupiah($a->amount) }}</td>
                        <td>{{ $a->tenor }} bln</td>
                        <td>{{ $a->branch->name }}</td>
                        <td><x-status-badge :status="$a->status" /></td>
                        <td>{{ tanggal($a->created_at) }}</td>
                        <td class="text-right"><a href="{{ route('customer.applications.show', $a) }}" class="font-semibold text-brand-700 hover:underline">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-500">Belum ada pengajuan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $applications->links() }}</div>
    </div>
</x-layouts.app>
