<x-layouts.public title="Produk Kredit">
    <section class="mx-auto max-w-7xl px-4 py-12">
        <h1 class="text-3xl font-bold text-slate-900">Produk Kredit</h1>
        <p class="mt-2 text-slate-500">Bandingkan suku bunga, plafon, dan jangka waktu setiap produk.</p>
        <div class="mt-8 grid gap-6 md:grid-cols-2">
            @foreach ($products as $p)
                <div class="card flex flex-col p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="badge bg-brand-50 text-brand-700">{{ $p->categoryLabel() }}</span>
                            <h2 class="mt-2 text-xl font-semibold">{{ $p->name }}</h2>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-extrabold text-brand-800">{{ number_format($p->interest_rate, 2, ',', '.') }}%</div>
                            <div class="text-xs text-slate-500">p.a. {{ $p->interest_method->label() }}</div>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">{{ $p->description }}</p>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-50 p-3"><dt class="text-slate-500">Plafon</dt><dd class="font-semibold">{{ rupiah($p->min_amount) }} – {{ rupiah($p->max_amount) }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-3"><dt class="text-slate-500">Jangka waktu</dt><dd class="font-semibold">{{ $p->min_tenor }} – {{ $p->max_tenor }} bulan</dd></div>
                        <div class="rounded-lg bg-slate-50 p-3"><dt class="text-slate-500">Provisi / Admin</dt><dd class="font-semibold">{{ $p->provision_fee }}% / {{ rupiah($p->admin_fee) }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-3"><dt class="text-slate-500">Agunan</dt><dd class="font-semibold">{{ $p->requires_collateral ? 'Diperlukan' : 'Tanpa agunan' }}</dd></div>
                    </dl>
                    <div class="mt-auto flex gap-2 pt-5">
                        <a href="{{ route('products.show', $p) }}" class="btn-secondary flex-1">Detail</a>
                        <a href="{{ route('simulation', ['product' => $p->id]) }}" class="btn-secondary flex-1">Simulasi</a>
                        <a href="{{ route('customer.applications.create', ['product' => $p->id]) }}" class="btn-primary flex-1">Ajukan</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
