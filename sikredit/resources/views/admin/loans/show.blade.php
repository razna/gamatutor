@php use App\Enums\Role; $u = auth()->user(); $kol = $loan->collectibilityEnum(); $active = $loan->status->value === 'active'; @endphp
<x-layouts.app :title="'Kredit '.$loan->number" :subtitle="$loan->customer->user->name.' · '.$loan->product->name.' · '.$loan->contract_number">
    <x-slot:actions>
        <a href="{{ route('admin.applications.show', $loan) }}" class="btn-secondary btn-sm">Berkas Pengajuan</a>
        <a href="{{ route('print.schedule', $loan) }}" target="_blank" class="btn-secondary btn-sm"><x-icon name="print" class="h-4 w-4" /> Jadwal</a>
    </x-slot:actions>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat label="Plafon" :value="rupiah_singkat($loan->approved_amount)" :hint="$loan->finalTenor().' bln · '.$loan->finalRate().'% · cair '.tanggal($loan->disbursed_at)" icon="wallet" />
        <x-stat label="Outstanding pokok" :value="rupiah_singkat($loan->outstanding_principal)" :hint="rupiah($loan->outstanding_principal)" icon="chart" tone="violet" />
        <x-stat label="Total tunggakan" :value="rupiah($loan->totalArrears())" :hint="$loan->days_past_due.' hari'" icon="alert" :tone="$loan->days_past_due ? 'rose' : 'green'" />
        <x-stat label="Kolektibilitas" :value="$kol ? $kol->value.' — '.$kol->label() : '-'" icon="shield" :tone="$kol && $kol->value > 1 ? 'amber' : 'green'" />
        <x-stat label="Restrukturisasi" :value="$loan->restructure_count.'×'" icon="cog" tone="amber" />
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-3" x-data="{ tab: 'jadwal' }">
        <div class="min-w-0 space-y-4 xl:col-span-2">
            <div class="flex gap-1 rounded-xl bg-white p-1 shadow-sm">
                @foreach (['jadwal' => 'Jadwal Angsuran', 'bayar' => 'Pembayaran ('.$loan->payments->count().')', 'collection' => 'Penagihan ('.$loan->collectionNotes->count().')', 'restruktur' => 'Restrukturisasi'] as $k => $l)
                    <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'bg-brand-700 text-white' : 'text-slate-600 hover:bg-slate-100'" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium">{{ $l }}</button>
                @endforeach
            </div>
            <div x-show="tab === 'jadwal'" class="card max-h-[600px] overflow-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Ke</th><th>Jatuh Tempo</th><th class="text-right">Pokok</th><th class="text-right">Bunga</th><th class="text-right">Angsuran</th><th class="text-right">Dibayar</th><th class="text-right">Denda</th><th>Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach ($loan->installments as $i)
                        @php [$l, $c] = $i->statusBadge(); @endphp
                        <tr class="{{ $i->status === 'cancelled' ? 'opacity-50 line-through' : '' }}">
                            <td>{{ $i->number }}</td><td>{{ tanggal($i->due_date) }}</td><td class="text-right">{{ rupiah($i->principal) }}</td><td class="text-right">{{ rupiah($i->interest) }}</td>
                            <td class="text-right font-semibold">{{ rupiah($i->amount) }}</td><td class="text-right">{{ rupiah($i->paid_amount) }}</td>
                            <td class="text-right {{ $i->remainingPenalty() > 0 ? 'text-rose-600' : '' }}">{{ rupiah($i->penalty) }}</td><td><span class="badge {{ $c }}">{{ $l }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div x-show="tab === 'bayar'" x-cloak class="card overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Kuitansi</th><th>Tanggal</th><th>Jenis</th><th>Metode</th><th class="text-right">Pokok</th><th class="text-right">Bunga</th><th class="text-right">Denda</th><th class="text-right">Total</th><th>Petugas</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($loan->payments as $p)
                        <tr><td>{{ $p->receipt_number }}</td><td>{{ tanggal($p->paid_at) }}</td><td>{{ $p->type === 'payoff' ? 'Pelunasan' : 'Angsuran' }}</td><td>{{ \App\Models\Payment::METHODS[$p->method] }}</td>
                            <td class="text-right">{{ rupiah($p->principal_paid) }}</td><td class="text-right">{{ rupiah($p->interest_paid) }}</td><td class="text-right">{{ rupiah($p->penalty_paid) }}</td><td class="text-right font-semibold">{{ rupiah($p->amount) }}</td>
                            <td>{{ $p->receiver?->name }}</td><td><a href="{{ route('print.receipt', $p) }}" target="_blank" class="text-brand-700 hover:underline">Cetak</a></td></tr>
                    @empty
                        <tr><td colspan="10" class="py-8 text-center text-slate-500">Belum ada pembayaran.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div x-show="tab === 'collection'" x-cloak class="space-y-4">
                @if ($active && $u->hasRole(Role::Collection))
                    <form method="POST" action="{{ route('admin.loans.note', $loan) }}" class="card card-body grid gap-3 md:grid-cols-4">
                        @csrf
                        <x-field label="Tindakan" name="action" :options="\App\Models\CollectionNote::ACTIONS" required />
                        <x-field label="Janji bayar (tgl)" name="promise_date" type="date" />
                        <x-field label="Janji bayar (Rp)" name="promise_amount" type="number" min="0" />
                        <div class="md:col-span-4"><x-field label="Hasil penagihan" name="result" type="textarea" required /></div>
                        <div class="md:col-span-4 flex justify-end"><button class="btn-primary">Simpan Catatan</button></div>
                    </form>
                @endif
                <div class="card card-body">
                    <ol class="space-y-4">
                        @forelse ($loan->collectionNotes as $n)
                            <li class="border-l-2 border-brand-200 pl-4 text-sm">
                                <div class="font-semibold">{{ \App\Models\CollectionNote::ACTIONS[$n->action] }} <span class="text-xs font-normal text-slate-500">· {{ $n->user->name }} · {{ tanggal($n->created_at, true) }}</span></div>
                                <p class="mt-1">{{ $n->result }}</p>
                                @if ($n->promise_date)<p class="mt-1 text-xs text-amber-700">Janji bayar {{ rupiah($n->promise_amount) }} pada {{ tanggal($n->promise_date) }}</p>@endif
                            </li>
                        @empty
                            <li class="text-center text-sm text-slate-500">Belum ada catatan penagihan.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
            <div x-show="tab === 'restruktur'" x-cloak class="space-y-4">
                @if ($active && $u->hasRole(Role::KepalaCabang, Role::Komite))
                    <form method="POST" action="{{ route('admin.loans.restructure', $loan) }}" class="card card-body grid gap-3 md:grid-cols-3">
                        @csrf
                        <div class="md:col-span-3 text-sm text-slate-600">Sisa pokok (dan bunga tertunggak) akan dijadwalkan ulang; denda dihapuskan. Angsuran lama yang belum dibayar dibatalkan.</div>
                        <x-field label="Skema" name="scheme" :options="\App\Models\Restructure::SCHEMES" required />
                        <x-field label="Tenor baru (bulan)" name="new_tenor" type="number" min="1" required />
                        <x-field label="Bunga baru (% p.a.)" name="new_rate" type="number" step="0.01" :value="$loan->finalRate()" required />
                        <div class="md:col-span-3"><x-field label="Alasan & analisis" name="reason" type="textarea" required /></div>
                        <div class="md:col-span-3 flex justify-end"><button class="btn-warning" onclick="return confirm('Proses restrukturisasi?')">Proses Restrukturisasi</button></div>
                    </form>
                @endif
                @forelse ($loan->restructures as $r)
                    <div class="card card-body text-sm">
                        <div class="font-semibold">{{ \App\Models\Restructure::SCHEMES[$r->scheme] }} <span class="text-xs font-normal text-slate-500">· {{ $r->user->name }} · {{ tanggal($r->created_at) }}</span></div>
                        <p class="mt-1">Pokok {{ rupiah($r->outstanding_before) }} · sisa tenor {{ $r->old_remaining_tenor }} → {{ $r->new_tenor }} bln · bunga {{ $r->old_rate }}% → {{ $r->new_rate }}%</p>
                        <p class="mt-1 text-slate-600">{{ $r->reason }}</p>
                    </div>
                @empty
                    <div class="card card-body text-center text-sm text-slate-500">Belum pernah direstrukturisasi.</div>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            @if ($active && $u->hasRole(Role::Teller))
                <form method="POST" action="{{ route('admin.loans.pay', $loan) }}" class="card">
                    @csrf
                    <div class="card-header"><div class="card-title">Terima Pembayaran Angsuran</div></div>
                    <div class="card-body space-y-3">
                        @php $next = $loan->installments->first(fn ($i) => in_array($i->status, ['unpaid', 'partial'])); $suggest = $loan->totalArrears() ?: ($next ? $next->remaining() + $next->remainingPenalty() : 0); @endphp
                        <div class="rounded-lg bg-slate-50 p-3 text-xs">Tagihan saat ini (tunggakan + denda): <strong>{{ rupiah($loan->totalArrears()) }}</strong>@if ($next)<br>Angsuran berikutnya ke-{{ $next->number }}: <strong>{{ rupiah($next->remaining()) }}</strong> ({{ tanggal($next->due_date) }})@endif</div>
                        <x-field label="Nominal (Rp)" name="amount" type="number" min="1000" :value="round($suggest)" required />
                        <x-field label="Metode" name="method" :options="\App\Models\Payment::METHODS" value="tunai" required />
                        <x-field label="No. referensi" name="reference" />
                        <x-field label="Tanggal bayar" name="paid_at" type="date" :value="today()->toDateString()" required />
                        <button class="btn-success w-full">Catat Pembayaran</button>
                        <p class="text-xs text-slate-500">Alokasi otomatis: denda → bunga → pokok, dimulai dari angsuran tertua.</p>
                    </div>
                </form>
            @endif
            @if ($quote)
                <div class="card">
                    <div class="card-header"><div class="card-title">Pelunasan Dipercepat</div></div>
                    <div class="card-body space-y-3">
                        <form class="flex gap-2"><input type="date" name="payoff_date" value="{{ $quote['as_of']->toDateString() }}" class="form-input"><button class="btn-secondary">Hitung</button></form>
                        <dl>
                            <div class="dl-row"><dt>Angsuran jatuh tempo</dt><dd>{{ rupiah($quote['arrears']) }}</dd></div>
                            <div class="dl-row"><dt>Denda</dt><dd>{{ rupiah($quote['penalty']) }}</dd></div>
                            <div class="dl-row"><dt>Sisa pokok</dt><dd>{{ rupiah($quote['principal']) }}</dd></div>
                            <div class="dl-row"><dt>Biaya pelunasan ({{ $quote['fee_pct'] }}%)</dt><dd>{{ rupiah($quote['fee']) }}</dd></div>
                            <div class="dl-row"><dt class="font-semibold text-slate-800">Total</dt><dd class="text-brand-800">{{ rupiah($quote['total']) }}</dd></div>
                        </dl>
                        @if ($u->hasRole(Role::Teller) && $quote['as_of']->isToday())
                            <form method="POST" action="{{ route('admin.loans.payoff', $loan) }}" class="space-y-2 border-t border-slate-100 pt-3">
                                @csrf
                                <input type="hidden" name="paid_at" value="{{ today()->toDateString() }}">
                                <x-field label="Metode" name="method" :options="\App\Models\Payment::METHODS" value="tunai" required />
                                <x-field label="No. referensi" name="reference" />
                                <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="confirm" value="1" class="rounded border-slate-300"> Dana {{ rupiah($quote['total']) }} telah diterima</label>
                                <button class="btn-primary w-full">Proses Pelunasan</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
            <div class="card card-body text-sm">
                <div class="card-title">Debitur</div>
                <dl class="mt-2">
                    <div class="dl-row"><dt>Nama</dt><dd>{{ $loan->customer->user->name }}</dd></div>
                    <div class="dl-row"><dt>Telepon</dt><dd>{{ $loan->customer->user->phone }}</dd></div>
                    <div class="dl-row"><dt>Kontak darurat</dt><dd>{{ $loan->customer->emergency_name }} · {{ $loan->customer->emergency_phone }}</dd></div>
                    <div class="dl-row"><dt>Rek. pencairan</dt><dd>{{ $loan->disbursement_bank }} {{ $loan->disbursement_account ? '••••'.substr($loan->disbursement_account, -4) : '' }}</dd></div>
                    <div class="dl-row"><dt>Cabang</dt><dd>{{ $loan->branch->name }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-layouts.app>
