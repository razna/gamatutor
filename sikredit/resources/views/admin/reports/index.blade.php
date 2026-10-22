<x-layouts.app title="Laporan" :subtitle="$title">
    <div class="card">
        <form class="grid gap-3 border-b border-slate-100 p-4 md:grid-cols-6">
            <select name="type" class="form-input md:col-span-2" onchange="this.form.submit()">
                @foreach (\App\Http\Controllers\Admin\ReportController::TYPES as $k => $v)<option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ $from }}" class="form-input" title="Dari tanggal">
            <input type="date" name="to" value="{{ $to }}" class="form-input" title="Sampai tanggal">
            <select name="product" class="form-input"><option value="">Semua produk</option>@foreach ($products as $p)<option value="{{ $p->id }}" @selected(request('product') == $p->id)>{{ $p->name }}</option>@endforeach</select>
            @if (auth()->user()->seesAllBranches())
                <select name="branch" class="form-input"><option value="">Semua cabang</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>@endforeach</select>
            @endif
            <div class="flex flex-wrap gap-2 md:col-span-6">
                <button class="btn-primary">Tampilkan</button>
                <button name="export" value="csv" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> Ekspor Excel (CSV)</button>
                <button name="export" value="print" formtarget="_blank" class="btn-secondary"><x-icon name="print" class="h-4 w-4" /> Cetak / PDF</button>
            </div>
        </form>
        <div class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($totals as $k => $v)
                <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">{{ $k }}</div><div class="font-bold">{{ $v }}</div></div>
            @endforeach
        </div>
        <div class="max-h-[600px] overflow-auto">
            <table class="table">
                <thead class="sticky top-0"><tr>@foreach ($columns as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="py-10 text-center text-slate-500">Tidak ada data pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
