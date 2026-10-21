<x-layouts.app :title="'Pinjaman '.$loan->contract_number" :subtitle="$loan->product->name">
    <x-slot:actions>
        <a href="{{ route('print.schedule', $loan) }}" target="_blank" class="btn-secondary btn-sm"><x-icon name="print" class="h-4 w-4" /> Jadwal</a>
        <a href="{{ route('print.contract', $loan) }}" target="_blank" class="btn-secondary btn-sm hidden sm:inline-flex"><x-icon name="doc" class="h-4 w-4" /> Perjanjian</a>
    </x-slot:actions>
    @php $kol = $loan->collectibilityEnum(); @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Plafon" :value="rupiah_singkat($loan->approved_amount)" :hint="$loan->approved_tenor.' bulan · '.$loan->finalRate().'% p.a.'" icon="wallet" />
        <x-stat label="Sisa pokok" :value="rupiah_singkat($loan->outstanding_principal)" :hint="rupiah($loan->outstanding_principal)" icon="chart" tone="violet" />
        <x-stat label="Tunggakan" :value="rupiah($loan->totalArrears())" :hint="$loan->days_past_due ? $loan->days_past_due.' hari' : 'Tidak ada'" icon="alert" :tone="$loan->days_past_due ? 'rose' : 'green'" />
        <x-stat label="Kolektibilitas" :value="$kol?->label() ?? 'Lunas'" icon="shield" :tone="$kol && $kol->value > 1 ? 'amber' : 'green'" />
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><div class="card-title">Jadwal Angsuran</div></div>
            <div class="max-h-[520px] overflow-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Ke</th><th>Jatuh Tempo</th><th class="text-right">Pokok</th><th class="text-right">Bunga</th><th class="text-right">Angsuran</th><th class="text-right">Denda</th><th>Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach ($loan->activeInstallments as $i)
                        @php [$l, $c] = $i->statusBadge(); @endphp
                        <tr>
                            <td>{{ $i->number }}</td><td>{{ tanggal($i->due_date) }}</td>
                            <td class="text-right">{{ rupiah($i->principal) }}</td><td class="text-right">{{ rupiah($i->interest) }}</td>
                            <td class="text-right font-semibold">{{ rupiah($i->amount) }}</td>
                            <td class="text-right {{ $i->penalty > 0 ? 'text-rose-600' : '' }}">{{ rupiah($i->penalty) }}</td>
                            <td><span class="badge {{ $c }}">{{ $l }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space-y-6">
            @if ($quote)
                <div class="card">
                    <div class="card-header"><div class="card-title">Simulasi Pelunasan Dipercepat</div></div>
                    <form class="card-body space-y-3">
                        <div class="flex gap-2"><input type="date" name="payoff_date" value="{{ $quote['as_of']->toDateString() }}" min="{{ today()->toDateString() }}" class="form-input"><button class="btn-secondary">Hitung</button></div>
                        <dl>
                            <div class="dl-row"><dt>Angsuran jatuh tempo</dt><dd>{{ rupiah($quote['arrears']) }}</dd></div>
                            <div class="dl-row"><dt>Denda</dt><dd>{{ rupiah($quote['penalty']) }}</dd></div>
                            <div class="dl-row"><dt>Sisa pokok</dt><dd>{{ rupiah($quote['principal']) }}</dd></div>
                            <div class="dl-row"><dt>Biaya pelunasan ({{ $quote['fee_pct'] }}%)</dt><dd>{{ rupiah($quote['fee']) }}</dd></div>
                            <div class="dl-row text-base"><dt class="font-semibold text-slate-800">Total pelunasan</dt><dd class="text-brand-800">{{ rupiah($quote['total']) }}</dd></div>
                        </dl>
                        <p class="text-xs text-slate-500">Bunga angsuran mendatang tidak dikenakan. Pelunasan dilakukan di teller cabang {{ $loan->branch->name }}.</p>
                    </form>
                </div>
            @endif
            <div class="card">
                <div class="card-header"><div class="card-title">Riwayat Pembayaran</div></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($loan->payments as $p)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <div><div class="font-medium">{{ rupiah($p->amount) }}</div><div class="text-xs text-slate-500">{{ tanggal($p->paid_at) }} · {{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }}</div></div>
                            <a href="{{ route('print.receipt', $p) }}" target="_blank" class="text-xs font-semibold text-brand-700 hover:underline">Kuitansi</a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Belum ada pembayaran.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card card-body text-sm">
                <div class="card-title">Cara Pembayaran</div>
                <ul class="mt-2 list-inside list-disc space-y-1 text-slate-600">
                    <li>Tunai di teller cabang {{ $loan->branch->name }}</li>
                    <li>Autodebet rekening tabungan</li>
                    <li>Transfer / Virtual Account (cantumkan No. {{ $loan->number }})</li>
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
