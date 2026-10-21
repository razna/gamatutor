<x-layouts.app title="Pengajuan Kredit" subtitle="Antrian kerja dan pencarian seluruh pengajuan">
    <div class="mb-4 flex gap-2">
        <a href="{{ route('admin.applications.index', ['scope' => 'queue']) }}" class="btn btn-sm {{ $scope === 'queue' && ! request('status') ? 'bg-brand-700 text-white' : 'bg-white text-slate-700 border border-slate-300' }}">Antrian Saya</a>
        <a href="{{ route('admin.applications.index', ['scope' => 'all']) }}" class="btn btn-sm {{ $scope === 'all' || request('status') ? 'bg-brand-700 text-white' : 'bg-white text-slate-700 border border-slate-300' }}">Semua Pengajuan</a>
    </div>
    <div class="card">
        <form class="grid gap-3 border-b border-slate-100 p-4 md:grid-cols-7">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <input name="q" value="{{ request('q') }}" placeholder="No. pengajuan / nama nasabah" class="form-input md:col-span-2">
            <select name="status" class="form-input"><option value="">Semua status</option>@foreach (\App\Enums\ApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach</select>
            <select name="product" class="form-input"><option value="">Semua produk</option>@foreach ($products as $p)<option value="{{ $p->id }}" @selected(request('product') == $p->id)>{{ $p->name }}</option>@endforeach</select>
            @if (auth()->user()->seesAllBranches())
                <select name="branch" class="form-input"><option value="">Semua cabang</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>
            @endif
            <select name="sla" class="form-input"><option value="">SLA: semua</option><option value="breached" @selected(request('sla') === 'breached')>Lewat SLA</option></select>
            <button class="btn-secondary">Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Pengajuan</th><th>Nasabah</th><th>Produk</th><th>Cabang</th><th class="text-right">Plafon</th><th>Tenor</th><th>Status</th><th>SLA</th><th>Diajukan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($applications as $a)
                    <tr class="cursor-pointer" onclick="location='{{ route('admin.applications.show', $a) }}'">
                        <td class="font-medium text-brand-700">{{ $a->number }}</td>
                        <td>{{ $a->customer->user->name }}</td>
                        <td>{{ $a->product->name }}</td>
                        <td>{{ $a->branch->name }}</td>
                        <td class="text-right">{{ rupiah($a->finalAmount()) }}</td>
                        <td>{{ $a->finalTenor() }} bln</td>
                        <td><x-status-badge :status="$a->status" /></td>
                        <td><x-sla :application="$a" /></td>
                        <td>{{ tanggal($a->submitted_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-slate-500">Tidak ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $applications->links() }}</div>
    </div>
</x-layouts.app>
