<x-layouts.app title="Pinjaman" subtitle="Portofolio kredit yang telah dicairkan">
    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.loans.index') }}" class="btn btn-sm {{ request('status') !== 'paid_off' ? 'bg-brand-700 text-white' : 'border border-slate-300 bg-white' }}">Aktif</a>
        <a href="{{ route('admin.loans.index', ['status' => 'paid_off']) }}" class="btn btn-sm {{ request('status') === 'paid_off' ? 'bg-brand-700 text-white' : 'border border-slate-300 bg-white' }}">Lunas</a>
    </div>
    <div class="card">
        <form class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input name="q" value="{{ request('q') }}" placeholder="No. / nama nasabah" class="form-input max-w-xs">
            <select name="kol" class="form-input max-w-xs"><option value="">Semua kolektibilitas</option>@foreach (\App\Enums\Collectibility::cases() as $k)<option value="{{ $k->value }}" @selected(request('kol') == $k->value)>{{ $k->value }} — {{ $k->label() }}</option>@endforeach</select>
            <button class="btn-secondary">Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Kredit</th><th>Nasabah</th><th>Produk</th><th>Tgl Cair</th><th class="text-right">Plafon</th><th class="text-right">Outstanding</th><th>Tunggakan</th><th>Kolektibilitas</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($loans as $l)
                    @php $kol = $l->collectibilityEnum(); @endphp
                    <tr class="cursor-pointer" onclick="location='{{ route('admin.loans.show', $l) }}'">
                        <td class="font-medium text-brand-700">{{ $l->number }}</td><td>{{ $l->customer->user->name }}</td><td>{{ $l->product->name }}</td>
                        <td>{{ tanggal($l->disbursed_at) }}</td><td class="text-right">{{ rupiah($l->approved_amount) }}</td><td class="text-right">{{ rupiah($l->outstanding_principal) }}</td>
                        <td>{{ $l->days_past_due ? $l->days_past_due.' hari' : '-' }}</td>
                        <td>@if ($kol && $l->status->value === 'active')<span class="badge {{ $kol->color() }}">{{ $kol->label() }}</span>@else <x-status-badge :status="$l->status" /> @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-500">Tidak ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $loans->links() }}</div>
    </div>
</x-layouts.app>
