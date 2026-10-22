<x-layouts.app title="Pinjaman & Angsuran">
    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($loans as $loan)
            @php $next = $loan->nextInstallment(); $paid = $loan->activeInstallments->where('status', 'paid')->count(); $total = $loan->activeInstallments->count(); @endphp
            <div class="card p-6">
                <div class="flex items-start justify-between">
                    <div><div class="text-lg font-semibold">{{ $loan->product->name }}</div><div class="text-xs text-slate-500">{{ $loan->contract_number }} · cair {{ tanggal($loan->disbursed_at) }}</div></div>
                    <x-status-badge :status="$loan->status" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3"><div class="text-slate-500">Plafon</div><div class="font-semibold">{{ rupiah($loan->approved_amount) }}</div></div>
                    <div class="rounded-lg bg-slate-50 p-3"><div class="text-slate-500">Sisa pokok</div><div class="font-semibold">{{ rupiah($loan->outstanding_principal) }}</div></div>
                    <div class="rounded-lg bg-slate-50 p-3"><div class="text-slate-500">Angsuran berikutnya</div><div class="font-semibold">{{ $next ? rupiah($next->remaining()) : '-' }}</div></div>
                    <div class="rounded-lg bg-slate-50 p-3"><div class="text-slate-500">Jatuh tempo</div><div class="font-semibold {{ $next?->daysOverdue() ? 'text-rose-600' : '' }}">{{ $next ? tanggal($next->due_date) : '-' }}</div></div>
                </div>
                <div class="mt-4 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $total ? round($paid / $total * 100) : 0 }}%"></div></div>
                <div class="mt-1 text-xs text-slate-500">{{ $paid }}/{{ $total }} angsuran lunas</div>
                <a href="{{ route('customer.loans.show', $loan) }}" class="btn-primary mt-4 w-full">Detail & Jadwal Angsuran</a>
            </div>
        @empty
            <div class="card p-10 text-center text-sm text-slate-500 lg:col-span-2">Belum ada pinjaman yang dicairkan.</div>
        @endforelse
    </div>
</x-layouts.app>
