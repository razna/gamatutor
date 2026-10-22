<x-layouts.app title="Dashboard Nasabah" :subtitle="'Selamat datang, '.auth()->user()->name">
    @unless ($customer->isComplete())
        <div class="mb-6 flex flex-col items-start justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center">
            <div><div class="font-semibold text-amber-900">Profil Anda belum lengkap</div><div class="text-sm text-amber-800">Lengkapi data diri, pekerjaan, dan penghasilan agar dapat mengajukan kredit.</div></div>
            <a href="{{ route('customer.profile.edit') }}" class="btn-warning">Lengkapi Profil</a>
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Pengajuan diproses" :value="$inProcess->count()" icon="inbox" />
        <x-stat label="Pinjaman aktif" :value="$activeLoans->count()" icon="wallet" tone="green" />
        <x-stat label="Sisa pokok pinjaman" :value="rupiah_singkat($outstanding)" :hint="rupiah($outstanding)" icon="chart" tone="violet" />
        <x-stat label="Angsuran berikutnya" :value="$nextDue ? rupiah($nextDue->remaining()) : '-'" :hint="$nextDue ? 'Jatuh tempo '.tanggal($nextDue->due_date) : 'Tidak ada tagihan'" icon="clock" :tone="$nextDue && $nextDue->daysOverdue() ? 'rose' : 'amber'" />
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div class="card-title">Pengajuan Terakhir</div>
                <a href="{{ route('customer.applications.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Ajukan Kredit</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($applications->take(5) as $a)
                    <a href="{{ route('customer.applications.show', $a) }}" class="block px-5 py-4 hover:bg-slate-50">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="font-semibold">{{ $a->product->name }}</div>
                                <div class="text-xs text-slate-500">{{ $a->number }} · {{ rupiah($a->amount) }} · {{ $a->tenor }} bulan</div>
                            </div>
                            <x-status-badge :status="$a->status" />
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm text-slate-500">Anda belum memiliki pengajuan kredit.</p>
                        <a href="{{ route('products') }}" class="btn-secondary mt-3">Lihat produk kredit</a>
                    </div>
                @endforelse
            </div>
        </div>
        <div class="space-y-6">
            <div class="card p-5">
                <div class="card-title">Akses Cepat</div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <a href="{{ route('simulation') }}" class="rounded-lg border border-slate-200 p-3 text-center hover:bg-slate-50"><x-icon name="calc" class="mx-auto h-6 w-6 text-brand-600" />Simulasi</a>
                    <a href="{{ route('customer.loans.index') }}" class="rounded-lg border border-slate-200 p-3 text-center hover:bg-slate-50"><x-icon name="wallet" class="mx-auto h-6 w-6 text-brand-600" />Angsuran</a>
                    <a href="{{ route('customer.tickets.create') }}" class="rounded-lg border border-slate-200 p-3 text-center hover:bg-slate-50"><x-icon name="chat" class="mx-auto h-6 w-6 text-brand-600" />Pengaduan</a>
                    <a href="{{ route('customer.profile.edit') }}" class="rounded-lg border border-slate-200 p-3 text-center hover:bg-slate-50"><x-icon name="user" class="mx-auto h-6 w-6 text-brand-600" />Profil</a>
                </div>
            </div>
            @foreach ($activeLoans as $loan)
                <div class="card p-5">
                    <div class="flex items-center justify-between"><div class="font-semibold">{{ $loan->product->name }}</div>
                        @if ($kol = $loan->collectibilityEnum())<span class="badge {{ $kol->color() }}">{{ $kol->label() }}</span>@endif</div>
                    @php $paid = $loan->activeInstallments->where('status', 'paid')->count(); $total = $loan->activeInstallments->count(); @endphp
                    <div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-emerald-500" style="width: {{ $total ? round($paid / $total * 100) : 0 }}%"></div></div>
                    <div class="mt-1 text-xs text-slate-500">{{ $paid }} dari {{ $total }} angsuran lunas</div>
                    <a href="{{ route('customer.loans.show', $loan) }}" class="mt-3 inline-block text-sm font-semibold text-brand-700 hover:underline">Lihat detail &rarr;</a>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
