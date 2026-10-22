@php use App\Enums\ApplicationStatus as S; use App\Enums\Collectibility; @endphp
<x-layouts.app title="Dashboard" :subtitle="$user->role->label().($user->branch ? ' · '.$user->branch->name : ' · Seluruh cabang')">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Antrian tugas saya" :value="$stats['my_queue']" hint="Pengajuan menunggu tindakan Anda" icon="inbox" />
        <x-stat label="Pengajuan bulan ini" :value="$stats['submitted_month']" :hint="$stats['in_process'].' sedang diproses'" icon="doc" tone="violet" />
        <x-stat label="Approval rate" :value="number_format($stats['approval_rate'], 1, ',', '.').'%'" :hint="'Rata-rata proses '.number_format($stats['avg_days'], 1, ',', '.').' hari'" icon="check" tone="green" />
        <x-stat label="Melewati SLA" :value="$stats['sla_breached']" hint="Pengajuan terlambat diproses" icon="clock" :tone="$stats['sla_breached'] ? 'rose' : 'green'" />
        <x-stat label="Kredit aktif" :value="number_format($stats['active_loans'], 0, ',', '.')" icon="wallet" />
        <x-stat label="Total outstanding" :value="rupiah_singkat($stats['outstanding'])" :hint="rupiah($stats['outstanding'])" icon="chart" tone="violet" />
        <x-stat label="Rasio NPL" :value="number_format($stats['npl_ratio'], 2, ',', '.').'%'" hint="Kolektibilitas 3–5" icon="alert" :tone="$stats['npl_ratio'] > 5 ? 'rose' : 'green'" />
        <x-stat label="Penerimaan hari ini" :value="rupiah_singkat($stats['collections_today'])" icon="wallet" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><div class="card-title">Tren Pengajuan & Pencairan {{ now()->year }}</div></div>
            <div class="card-body"><canvas id="trend" height="110"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-title">Pengajuan per Status</div></div>
            <div class="card-body"><canvas id="status" height="220"></canvas></div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><div class="card-title">Antrian Tugas</div><a href="{{ route('admin.applications.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Lihat semua</a></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>No.</th><th>Nasabah / Produk</th><th class="text-right">Plafon</th><th>Status</th><th>SLA</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($queue as $a)
                        <tr class="cursor-pointer" onclick="location='{{ route('admin.applications.show', $a) }}'">
                            <td class="font-medium text-brand-700">{{ $a->number }}</td><td><div>{{ $a->customer->user->name }}</div><div class="text-xs text-slate-500">{{ $a->product->name }}</div></td>
                            <td class="text-right">{{ rupiah($a->finalAmount()) }}</td><td><x-status-badge :status="$a->status" /></td><td><x-sla :application="$a" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-500">Tidak ada antrian. 🎉</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><div class="card-title">Kolektibilitas Kredit Aktif</div></div>
                <div class="card-body space-y-2">
                    @php $kolTotal = max(1, $byKol->sum()); @endphp
                    @foreach (Collectibility::cases() as $k)
                        <div>
                            <div class="flex justify-between text-xs"><span>{{ $k->value }} · {{ $k->label() }}</span><span class="font-semibold">{{ $byKol[$k->value] ?? 0 }}</span></div>
                            <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ ['', 'bg-emerald-500', 'bg-yellow-400', 'bg-amber-500', 'bg-orange-500', 'bg-rose-600'][$k->value] }}" style="width: {{ round(($byKol[$k->value] ?? 0) / $kolTotal * 100) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title">Aktivitas Terbaru</div></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($recent as $log)
                        <li class="px-5 py-3">
                            <a href="{{ route('admin.applications.show', $log->loan_application_id) }}" class="font-medium text-brand-700">{{ $log->application->number }}</a>
                            → <span class="text-slate-700">{{ $log->to_status->label() }}</span>
                            <div class="text-xs text-slate-500">{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at->locale('id')->diffForHumans() }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @if ($byProduct->isNotEmpty())
        <div class="card mt-6">
            <div class="card-header"><div class="card-title">Portofolio Kredit per Kategori (Rp juta)</div></div>
            <div class="card-body"><canvas id="product" height="70"></canvas></div>
        </div>
    @endif

    @push('scripts')
    <script type="module">
        const brand = '#1549de', gold = '#e9a92a';
        new Chart(document.getElementById('trend'), {
            data: {
                labels: @js($chart['labels']),
                datasets: [
                    { type: 'bar', label: 'Pengajuan (unit)', data: @js($chart['submitted']), backgroundColor: brand, borderRadius: 4, yAxisID: 'y' },
                    { type: 'line', label: 'Pencairan (Rp juta)', data: @js($chart['disbursed']), borderColor: gold, backgroundColor: gold, tension: 0, yAxisID: 'y1' },
                ],
            },
            options: { responsive: true, interaction: { mode: 'index', intersect: false }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } } } },
        });
        const statusLabels = @js(collect(S::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]));
        const byStatus = @js($byStatus);
        new Chart(document.getElementById('status'), {
            type: 'doughnut',
            data: { labels: Object.keys(byStatus).map(k => statusLabels[k]), datasets: [{ data: Object.values(byStatus), backgroundColor: ['#0ea5e9', '#f59e0b', '#6366f1', '#8b5cf6', '#14b8a6', '#f43f5e', '#0d9488', '#10b981', '#22c55e', '#94a3b8'] }] },
            options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } },
        });
        @if ($byProduct->isNotEmpty())
        new Chart(document.getElementById('product'), {
            type: 'bar',
            data: { labels: @js($byProduct->keys()), datasets: [{ label: 'Rp juta', data: @js($byProduct->values()->map(fn ($v) => round($v / 1e6, 1))), backgroundColor: brand, borderRadius: 4 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } } },
        });
        @endif
    </script>
    @endpush
</x-layouts.app>
