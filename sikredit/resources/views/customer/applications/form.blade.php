@php
    $editing = $application->exists;
    $productData = $products->keyBy('id')->map(fn ($p) => [
        'name' => $p->name, 'rate' => $p->interest_rate, 'method' => $p->interest_method->value, 'methodLabel' => $p->interest_method->label(),
        'min' => $p->min_amount, 'max' => $p->max_amount, 'minTenor' => $p->min_tenor, 'maxTenor' => $p->max_tenor,
        'collateral' => $p->requires_collateral, 'provision' => $p->provision_fee, 'admin' => $p->admin_fee,
        'docs' => $p->documentTypes->map(fn ($d) => $d->name.($d->pivot->is_required ? '' : ' (opsional)'))->values(),
    ]);
    $collaterals = old('collaterals', $application->collaterals?->map->only('type', 'description', 'ownership_number', 'owner_name', 'location', 'estimated_value')->values()->all() ?? []);
    $income = $customer->totalIncome();
@endphp
<x-layouts.app :title="$editing ? 'Ubah Pengajuan '.$application->number : 'Ajukan Kredit Baru'">
    <form method="POST" action="{{ $editing ? route('customer.applications.update', $application) : route('customer.applications.store') }}"
          x-data="applicationWizard()" @submit="submitting = true" novalidate>
        @csrf @if ($editing) @method('PUT') @endif

        <ol class="mb-6 grid grid-cols-4 gap-2 text-xs font-medium sm:text-sm">
            <template x-for="(label, i) in steps" :key="i">
                <li @click="if (i < step) step = i" :class="i === step ? 'border-brand-600 text-brand-700' : (i < step ? 'border-emerald-500 text-emerald-700 cursor-pointer' : 'border-slate-200 text-slate-400')" class="border-t-4 pt-2">
                    <span x-text="(i + 1) + '. ' + label"></span>
                </li>
            </template>
        </ol>

        {{-- Langkah 1: Produk --}}
        <div x-show="step === 0" class="card">
            <div class="card-header"><div class="card-title">Pilih produk kredit & cabang</div></div>
            <div class="card-body space-y-5">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $p)
                        <label class="cursor-pointer rounded-xl border-2 p-4 transition" :class="product == {{ $p->id }} ? 'border-brand-600 bg-brand-50' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="credit_product_id" value="{{ $p->id }}" x-model="product" class="sr-only">
                            <div class="flex items-center justify-between"><span class="badge bg-white text-brand-700">{{ $p->category }}</span><span class="text-sm font-bold text-brand-800">{{ $p->interest_rate }}% p.a.</span></div>
                            <div class="mt-2 font-semibold">{{ $p->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ rupiah($p->min_amount) }} – {{ rupiah($p->max_amount) }} · {{ $p->min_tenor }}–{{ $p->max_tenor }} bln</div>
                        </label>
                    @endforeach
                </div>
                @error('credit_product_id')<p class="form-error">{{ $message }}</p>@enderror
                <x-field class="max-w-md" label="Cabang pengajuan" name="branch_id" :value="$application->branch_id" :options="$branches->pluck('name', 'id')" required hint="Cabang terdekat untuk survei, akad, dan pencairan." />
                <template x-if="current">
                    <div class="rounded-lg bg-slate-50 p-4 text-sm">
                        <div class="font-semibold">Dokumen yang perlu disiapkan:</div>
                        <ul class="mt-2 grid list-inside list-disc gap-1 sm:grid-cols-2"><template x-for="d in current.docs"><li x-text="d"></li></template></ul>
                    </div>
                </template>
            </div>
        </div>

        {{-- Langkah 2: Plafon --}}
        <div x-show="step === 1" x-cloak class="grid gap-6 lg:grid-cols-3">
            <div class="card lg:col-span-2">
                <div class="card-header"><div class="card-title">Plafon, jangka waktu & tujuan</div></div>
                <div class="card-body grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="form-label">Plafon pinjaman (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" name="amount" x-model.number="amount" :min="current?.min" :max="current?.max" step="100000" class="form-input" required>
                        <p class="mt-1 text-xs text-slate-500" x-show="current" x-text="'Rentang: ' + rupiah(current?.min) + ' – ' + rupiah(current?.max)"></p>
                        @error('amount')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Jangka waktu (bulan) <span class="text-rose-500">*</span></label>
                        <input type="number" name="tenor" x-model.number="tenor" :min="current?.minTenor" :max="current?.maxTenor" class="form-input" required>
                        <p class="mt-1 text-xs text-slate-500" x-show="current" x-text="'Rentang: ' + current?.minTenor + ' – ' + current?.maxTenor + ' bulan'"></p>
                        @error('tenor')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <x-field class="md:col-span-2" label="Tujuan penggunaan" name="purpose" :value="$application->purpose" :options="\App\Models\LoanApplication::PURPOSES" required />
                    <x-field class="md:col-span-2" label="Keterangan tujuan" name="purpose_detail" type="textarea" :value="$application->purpose_detail" placeholder="Contoh: pembelian rumah tipe 45 di Perumahan …" />
                </div>
            </div>
            <div class="card h-fit p-5">
                <div class="card-title">Estimasi angsuran</div>
                <div class="mt-3 text-3xl font-extrabold text-brand-800" x-text="rupiah(sim.monthly)"></div>
                <div class="text-xs text-slate-500" x-text="current ? current.methodLabel + ', ' + current.rate + '% p.a.' : ''"></div>
                <dl class="mt-4">
                    <div class="dl-row"><dt>Total bunga</dt><dd x-text="rupiah(sim.totalInterest)"></dd></div>
                    <div class="dl-row"><dt>Provisi</dt><dd x-text="rupiah(amount * (current?.provision || 0) / 100)"></dd></div>
                    <div class="dl-row"><dt>Biaya admin</dt><dd x-text="rupiah(current?.admin || 0)"></dd></div>
                    <div class="dl-row"><dt>Rasio cicilan (DSR)</dt><dd><span :class="dsr > {{ (float) \App\Models\Setting::get('max_dsr') }} ? 'text-rose-600' : 'text-emerald-700'" x-text="dsr.toFixed(1) + '%'"></span></dd></div>
                </dl>
                <p x-show="dsr > {{ (float) \App\Models\Setting::get('max_dsr') }}" class="mt-3 rounded-lg bg-rose-50 p-2 text-xs text-rose-700">DSR melebihi batas {{ \App\Models\Setting::get('max_dsr') }}%. Pertimbangkan menurunkan plafon atau memperpanjang tenor.</p>
            </div>
        </div>

        {{-- Langkah 3: Agunan --}}
        <div x-show="step === 2" x-cloak class="card">
            <div class="card-header">
                <div class="card-title">Data agunan <span x-show="current?.collateral" class="text-sm font-normal text-rose-600">(wajib untuk produk ini)</span><span x-show="current && !current.collateral" class="text-sm font-normal text-slate-500">(opsional)</span></div>
                <button type="button" @click="collaterals.push({type: 'tanah_bangunan', description: '', ownership_number: '', owner_name: '', location: '', estimated_value: ''})" class="btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Tambah agunan</button>
            </div>
            <div class="card-body space-y-4">
                <template x-for="(c, i) in collaterals" :key="i">
                    <div class="grid gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-3">
                        <div>
                            <label class="form-label">Jenis agunan</label>
                            <select :name="`collaterals[${i}][type]`" x-model="c.type" class="form-input">
                                @foreach (\App\Models\Collateral::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2"><label class="form-label">Deskripsi</label><input :name="`collaterals[${i}][description]`" x-model="c.description" class="form-input" required placeholder="Rumah LT 120 m² / LB 90 m², Toyota Avanza 2021, …"></div>
                        <div><label class="form-label">No. sertifikat / BPKB</label><input :name="`collaterals[${i}][ownership_number]`" x-model="c.ownership_number" class="form-input"></div>
                        <div><label class="form-label">Atas nama</label><input :name="`collaterals[${i}][owner_name]`" x-model="c.owner_name" class="form-input"></div>
                        <div><label class="form-label">Taksiran nilai (Rp)</label><input type="number" min="0" :name="`collaterals[${i}][estimated_value]`" x-model="c.estimated_value" class="form-input" required></div>
                        <div class="md:col-span-2"><label class="form-label">Lokasi</label><input :name="`collaterals[${i}][location]`" x-model="c.location" class="form-input"></div>
                        <div class="flex items-end justify-end"><button type="button" @click="collaterals.splice(i, 1)" class="btn-sm btn text-rose-600 hover:bg-rose-50">Hapus</button></div>
                    </div>
                </template>
                <p x-show="collaterals.length === 0" class="py-6 text-center text-sm text-slate-500">Belum ada agunan.</p>
                @error('collaterals')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Langkah 4: Review --}}
        <div x-show="step === 3" x-cloak class="card">
            <div class="card-header"><div class="card-title">Tinjau pengajuan</div></div>
            <div class="card-body grid gap-6 md:grid-cols-2">
                <dl>
                    <div class="dl-row"><dt>Produk</dt><dd x-text="current?.name"></dd></div>
                    <div class="dl-row"><dt>Plafon</dt><dd x-text="rupiah(amount)"></dd></div>
                    <div class="dl-row"><dt>Jangka waktu</dt><dd x-text="tenor + ' bulan'"></dd></div>
                    <div class="dl-row"><dt>Suku bunga</dt><dd x-text="current?.rate + '% (' + current?.methodLabel + ')'"></dd></div>
                    <div class="dl-row"><dt>Estimasi angsuran</dt><dd x-text="rupiah(sim.monthly)"></dd></div>
                    <div class="dl-row"><dt>Jumlah agunan</dt><dd x-text="collaterals.length"></dd></div>
                </dl>
                <div class="rounded-lg bg-brand-50 p-4 text-sm text-brand-900">
                    <div class="font-semibold">Langkah selanjutnya</div>
                    <ol class="mt-2 list-inside list-decimal space-y-1">
                        <li>Simpan pengajuan sebagai draft.</li>
                        <li>Unggah dokumen persyaratan pada halaman detail pengajuan.</li>
                        <li>Klik <strong>Kirim Pengajuan</strong> untuk diproses petugas.</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-between">
            <button type="button" x-show="step > 0" @click="step--" class="btn-secondary">&larr; Sebelumnya</button>
            <span x-show="step === 0"></span>
            <button type="button" x-show="step < 3" @click="next()" class="btn-primary">Selanjutnya &rarr;</button>
            <button type="submit" x-show="step === 3" :disabled="submitting" class="btn-success">{{ $editing ? 'Simpan Perubahan' : 'Simpan Draft & Lanjut Unggah Dokumen' }}</button>
        </div>
    </form>

    @push('scripts')
    <script>
        function applicationWizard() {
            const products = @js($productData);
            return {
                steps: ['Produk', 'Plafon & Tujuan', 'Agunan', 'Tinjau'],
                step: {{ $errors->any() ? ($errors->has('collaterals') || $errors->has('collaterals.*') ? 2 : ($errors->hasAny(['amount', 'tenor', 'purpose']) ? 1 : 0)) : 0 }},
                submitting: false,
                product: @js((string) old('credit_product_id', $application->credit_product_id)),
                amount: {{ (float) old('amount', $application->amount ?: 0) ?: 'null' }},
                tenor: {{ (int) old('tenor', $application->tenor ?: 0) ?: 'null' }},
                collaterals: @js($collaterals),
                income: {{ $income }},
                existing: {{ (float) $customer->existing_installments }},
                get current() { return products[this.product] || null; },
                get sim() {
                    const s = loanSimulator({ amount: this.amount || 0, tenor: this.tenor || 1, rate: this.current?.rate || 0, method: this.current?.method || 'anuitas' });
                    return { monthly: s.monthly, totalInterest: s.totalInterest };
                },
                get dsr() { return this.income > 0 ? (this.existing + this.sim.monthly) / this.income * 100 : 0; },
                next() {
                    if (this.step === 0 && (!this.product || !document.querySelector('[name=branch_id]').value)) { alert('Pilih produk dan cabang terlebih dahulu.'); return; }
                    if (this.step === 0 && !this.amount) { this.amount = this.current.min; this.tenor = this.current.minTenor; }
                    if (this.step === 1) {
                        const c = this.current;
                        if (!this.amount || this.amount < c.min || this.amount > c.max) { alert('Plafon harus antara ' + rupiah(c.min) + ' dan ' + rupiah(c.max) + '.'); return; }
                        if (!this.tenor || this.tenor < c.minTenor || this.tenor > c.maxTenor) { alert('Tenor harus antara ' + c.minTenor + ' dan ' + c.maxTenor + ' bulan.'); return; }
                        if (!document.querySelector('[name=purpose]').value) { alert('Pilih tujuan penggunaan kredit.'); return; }
                        if (c.collateral && this.collaterals.length === 0) this.collaterals.push({type: 'tanah_bangunan', description: '', ownership_number: '', owner_name: '', location: '', estimated_value: ''});
                    }
                    if (this.step === 2 && this.current?.collateral && this.collaterals.length === 0) { alert('Produk ini mensyaratkan agunan.'); return; }
                    this.step++;
                },
            };
        }
    </script>
    @endpush
</x-layouts.app>
