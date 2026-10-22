<x-layouts.public title="Simulasi Kredit">
    <section class="mx-auto max-w-6xl px-4 py-12">
        <h1 class="text-3xl font-bold">Simulasi Kredit</h1>
        <p class="mt-2 text-slate-500">Hitung angsuran dengan metode bunga flat, efektif, atau anuitas beserta tabel angsurannya.</p>

        <form method="GET" class="card mt-6 grid gap-4 p-6 md:grid-cols-6" x-data="{ products: @js($products->keyBy('id')->map(fn ($p) => ['rate' => $p->interest_rate, 'method' => $p->interest_method->value, 'min' => $p->min_tenor, 'max' => $p->max_tenor])) }">
            <div class="md:col-span-2">
                <label class="form-label">Produk (opsional)</label>
                <select name="product" class="form-input" @change="if (products[$event.target.value]) { $refs.rate.value = products[$event.target.value].rate; $refs.method.value = products[$event.target.value].method }">
                    <option value="">— Umum —</option>
                    @foreach ($products as $p)<option value="{{ $p->id }}" @selected(request('product') == $p->id)>{{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="form-label">Plafon (Rp)</label><input type="number" name="amount" min="1000000" step="100000" required value="{{ request('amount', 100000000) }}" class="form-input"></div>
            <div><label class="form-label">Tenor (bulan)</label><input type="number" name="tenor" min="1" max="360" required value="{{ request('tenor', 36) }}" class="form-input"></div>
            <div><label class="form-label">Bunga (% p.a.)</label><input x-ref="rate" type="number" name="rate" step="0.01" min="0" max="50" value="{{ request('rate', $product?->interest_rate ?? 10) }}" class="form-input"></div>
            <div><label class="form-label">Metode</label>
                <select x-ref="method" name="method" class="form-input">
                    @foreach (\App\Enums\InterestMethod::cases() as $m)<option value="{{ $m->value }}" @selected(request('method', $product?->interest_method?->value ?? 'anuitas') === $m->value)>{{ $m->label() }}</option>@endforeach
                </select>
            </div>
            <div class="md:col-span-6 flex justify-end"><button class="btn-primary">Hitung Simulasi</button></div>
        </form>

        @if ($result)
            <div class="mt-6 grid gap-4 md:grid-cols-4">
                <x-stat label="{{ $result['method']->value === 'efektif' ? 'Angsuran pertama' : 'Angsuran / bulan' }}" :value="rupiah($result['first_installment'])" icon="wallet" />
                <x-stat label="Total bunga" :value="rupiah($result['total_interest'])" icon="chart" tone="amber" />
                <x-stat label="Total pembayaran" :value="rupiah($result['total_payment'])" icon="calc" tone="violet" />
                <x-stat label="Dana diterima (estimasi)" :value="rupiah($result['net_received'])" :hint="'Setelah provisi '.rupiah($result['provision']).' & admin '.rupiah($result['admin_fee'])" icon="check" tone="green" />
            </div>
            <div class="card mt-6">
                <div class="card-header"><div class="card-title">Tabel Angsuran — {{ rupiah($result['amount']) }}, {{ $result['tenor'] }} bulan, {{ $result['rate'] }}% p.a. ({{ $result['method']->label() }})</div></div>
                <div class="max-h-[480px] overflow-auto">
                    <table class="table">
                        <thead class="sticky top-0"><tr><th>Bulan</th><th class="text-right">Pokok</th><th class="text-right">Bunga</th><th class="text-right">Angsuran</th><th class="text-right">Sisa Pokok</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach ($result['rows'] as $row)
                            <tr><td>{{ $row['number'] }}</td><td class="text-right">{{ rupiah($row['principal']) }}</td><td class="text-right">{{ rupiah($row['interest']) }}</td><td class="text-right font-semibold">{{ rupiah($row['amount']) }}</td><td class="text-right">{{ rupiah($row['balance_after']) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">* Simulasi bersifat ilustrasi. Nilai final ditentukan berdasarkan hasil analisis dan persetujuan kredit.</p>
        @endif

        <div class="mt-10 grid gap-4 md:grid-cols-3">
            <div class="card p-5"><div class="font-semibold">Flat</div><p class="mt-1 text-sm text-slate-500">Bunga dihitung dari plafon awal sehingga angsuran tetap setiap bulan.</p></div>
            <div class="card p-5"><div class="font-semibold">Efektif (menurun)</div><p class="mt-1 text-sm text-slate-500">Bunga dihitung dari sisa pokok; angsuran menurun setiap bulan.</p></div>
            <div class="card p-5"><div class="font-semibold">Anuitas</div><p class="mt-1 text-sm text-slate-500">Angsuran tetap, porsi bunga besar di awal dan porsi pokok membesar di akhir.</p></div>
        </div>
    </section>
</x-layouts.public>
