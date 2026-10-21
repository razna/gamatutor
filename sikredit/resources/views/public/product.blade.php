<x-layouts.public :title="$product->name">
    <section class="mx-auto max-w-5xl px-4 py-12">
        <a href="{{ route('products') }}" class="text-sm text-brand-700 hover:underline">&larr; Semua produk</a>
        <div class="mt-4 grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <span class="badge bg-brand-50 text-brand-700">{{ $product->categoryLabel() }}</span>
                <h1 class="mt-2 text-3xl font-bold">{{ $product->name }}</h1>
                <p class="mt-4 text-slate-600">{{ $product->description }}</p>

                <h2 class="mt-8 text-lg font-semibold">Persyaratan Umum</h2>
                <div class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $product->requirements ?: '-' }}</div>

                <h2 class="mt-8 text-lg font-semibold">Dokumen yang Diperlukan</h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($product->documentTypes as $d)
                        <li class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                            <x-icon name="doc" class="h-4 w-4 text-brand-600" /> {{ $d->name }}
                            @if (! $d->pivot->is_required)<span class="ml-auto text-xs text-slate-400">opsional</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card h-fit p-6">
                <div class="text-sm text-slate-500">Suku bunga</div>
                <div class="text-3xl font-extrabold text-brand-800">{{ number_format($product->interest_rate, 2, ',', '.') }}% <span class="text-sm font-medium text-slate-500">p.a.</span></div>
                <dl class="mt-4">
                    <div class="dl-row"><dt>Metode bunga</dt><dd>{{ $product->interest_method->label() }}</dd></div>
                    <div class="dl-row"><dt>Plafon minimum</dt><dd>{{ rupiah($product->min_amount) }}</dd></div>
                    <div class="dl-row"><dt>Plafon maksimum</dt><dd>{{ rupiah($product->max_amount) }}</dd></div>
                    <div class="dl-row"><dt>Jangka waktu</dt><dd>{{ $product->min_tenor }}–{{ $product->max_tenor }} bln</dd></div>
                    <div class="dl-row"><dt>Provisi</dt><dd>{{ $product->provision_fee }}%</dd></div>
                    <div class="dl-row"><dt>Biaya admin</dt><dd>{{ rupiah($product->admin_fee) }}</dd></div>
                    <div class="dl-row"><dt>Denda keterlambatan</dt><dd>{{ $product->penalty_rate }}%/hari</dd></div>
                    @if ($product->requires_collateral)<div class="dl-row"><dt>LTV maksimum</dt><dd>{{ $product->max_ltv }}%</dd></div>@endif
                </dl>
                <a href="{{ route('customer.applications.create', ['product' => $product->id]) }}" class="btn-primary mt-5 w-full">Ajukan Produk Ini</a>
                <a href="{{ route('simulation', ['product' => $product->id]) }}" class="btn-secondary mt-2 w-full">Simulasi Angsuran</a>
            </div>
        </div>
    </section>
</x-layouts.public>
