@php $an = $app->analysis; @endphp
@if ($an)
    <div class="card mb-6">
        <div class="card-header">
            <div class="card-title">Hasil Credit Scoring</div>
            <span class="text-xs text-slate-500">{{ $an->analyst->name }} · {{ tanggal($an->updated_at, true) }}</span>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap items-center gap-6">
                <div class="flex h-24 w-24 flex-col items-center justify-center rounded-full {{ in_array($an->grade, ['A', 'B']) ? 'bg-emerald-100 text-emerald-700' : ($an->grade === 'C' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                    <div class="text-3xl font-extrabold">{{ $an->grade }}</div><div class="text-xs font-semibold">{{ number_format($an->total_score, 1, ',', '.') }}</div>
                </div>
                <div class="flex-1 text-sm">
                    <div class="font-semibold">{{ \App\Services\CreditScoringService::gradeDescription($an->grade) }}</div>
                    <div class="mt-1">Rekomendasi analis: <span class="badge {{ $an->recommendation === 'approve' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $an->recommendation === 'approve' ? 'SETUJU' : 'TOLAK' }}</span></div>
                    <div class="mt-1 text-slate-600">Rekomendasi: {{ rupiah($an->recommended_amount) }} · {{ $an->recommended_tenor }} bln · {{ $an->recommended_rate }}% — angsuran {{ rupiah($an->new_installment) }}</div>
                    <div class="text-slate-600">DSR {{ $an->dsr }}% · LTV {{ $an->ltv !== null ? $an->ltv.'%' : '-' }} · SLIK Kol {{ $an->slik_collectibility }}</div>
                </div>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-5">
                @foreach (\App\Models\CreditAnalysis::ASPECTS as $k => [$label, $desc])
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">{{ $label }} ({{ $weights[$k] }}%)</div>
                        <div class="text-xl font-bold">{{ $an->{$k.'_score'} }}</div>
                        <div class="mt-1 h-1.5 rounded-full bg-slate-200"><div class="h-1.5 rounded-full bg-brand-600" style="width: {{ $an->{$k.'_score'} }}%"></div></div>
                        @if ($an->{$k.'_notes'})<div class="mt-2 text-xs text-slate-600">{{ $an->{$k.'_notes'} }}</div>@endif
                    </div>
                @endforeach
            </div>
            @if ($an->slik_notes)<p class="mt-4 text-sm"><span class="font-medium">Catatan SLIK:</span> {{ $an->slik_notes }}</p>@endif
            @if ($an->summary)<p class="mt-2 text-sm"><span class="font-medium">Kesimpulan:</span> {{ $an->summary }}</p>@endif
        </div>
    </div>
@endif

@if ($isAnalyst)
    @php $maxDsr = (float) \App\Models\Setting::get('max_dsr'); @endphp
    <form method="POST" action="{{ route('admin.process.analyze', $app) }}" class="card"
          x-data="{
            w: @js($weights),
            s: { @foreach (\App\Models\CreditAnalysis::ASPECTS as $k => $_) {{ $k }}: {{ (int) old($k.'_score', $an?->{$k.'_score'} ?? 70) }}, @endforeach },
            kol: {{ (int) old('slik_collectibility', $an?->slik_collectibility ?? 1) }},
            amount: {{ (float) old('recommended_amount', $an?->recommended_amount ?? $app->amount) }},
            tenor: {{ (int) old('recommended_tenor', $an?->recommended_tenor ?? $app->tenor) }},
            rate: {{ (float) old('recommended_rate', $an?->recommended_rate ?? $app->interest_rate) }},
            income: {{ $netIncome }}, existing: {{ (float) $customer->existing_installments }}, coll: {{ $app->totalCollateralValue() }}, maxLtv: {{ $app->product->max_ltv }}, needColl: {{ $app->product->requires_collateral ? 'true' : 'false' }},
            get inst() { return loanSimulator({ amount: this.amount, tenor: this.tenor, rate: this.rate, method: '{{ $app->interest_method->value }}' }).monthly },
            get dsr() { return this.income > 0 ? (this.existing + this.inst) / this.income * 100 : 100 },
            get ltv() { return this.coll > 0 ? this.amount / this.coll * 100 : null },
            get base() { let t = 0, sw = 0; for (const k in this.w) { t += this.s[k] * this.w[k]; sw += this.w[k]; } return t / (sw || 100) },
            get total() { let t = this.base; if (this.kol >= 3) t -= 30; else if (this.kol == 2) t -= 10; if (this.dsr > {{ $maxDsr }}) t -= 15; if (this.needColl && this.ltv !== null && this.ltv > this.maxLtv) t -= 10; return Math.max(0, Math.min(100, t)) },
            get grade() { const t = this.total; return t >= 85 ? 'A' : t >= 75 ? 'B' : t >= 65 ? 'C' : t >= 50 ? 'D' : 'E' },
          }">
        @csrf
        <div class="card-header"><div class="card-title">Form Analisis 5C & Scoring</div>
            <div class="text-right"><span class="text-xs text-slate-500">Skor sementara</span> <span class="text-lg font-extrabold text-brand-800" x-text="total.toFixed(1) + ' (' + grade + ')'"></span></div></div>
        <div class="card-body space-y-5">
            @foreach (\App\Models\CreditAnalysis::ASPECTS as $k => [$label, $desc])
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <div class="flex justify-between"><label class="form-label">{{ $label }} <span class="text-xs font-normal text-slate-400">bobot {{ $weights[$k] }}%</span></label><span class="text-sm font-bold" x-text="s.{{ $k }}"></span></div>
                        <input type="range" min="0" max="100" name="{{ $k }}_score" x-model.number="s.{{ $k }}" class="w-full accent-brand-700">
                        <p class="text-xs text-slate-500">{{ $desc }}</p>
                    </div>
                    <textarea name="{{ $k }}_notes" rows="2" class="form-input md:col-span-2" placeholder="Catatan {{ strtolower($label) }}">{{ old($k.'_notes', $an?->{$k.'_notes'}) }}</textarea>
                </div>
            @endforeach
            <div class="grid gap-4 border-t border-slate-100 pt-5 md:grid-cols-3">
                <div>
                    <label class="form-label">Hasil SLIK OJK (kolektibilitas terburuk)</label>
                    <select name="slik_collectibility" x-model.number="kol" class="form-input">
                        @foreach (\App\Enums\Collectibility::cases() as $c)<option value="{{ $c->value }}">{{ $c->value }} — {{ $c->label() }}</option>@endforeach
                    </select>
                </div>
                <x-field class="md:col-span-2" label="Catatan SLIK" name="slik_notes" :value="$an?->slik_notes" placeholder="Fasilitas di bank lain, riwayat tunggakan, dll." />
                <div><label class="form-label">Plafon rekomendasi (Rp)</label><input type="number" name="recommended_amount" x-model.number="amount" class="form-input" required></div>
                <div><label class="form-label">Tenor rekomendasi (bln)</label><input type="number" name="recommended_tenor" x-model.number="tenor" class="form-input" required></div>
                <div><label class="form-label">Bunga rekomendasi (% p.a.)</label><input type="number" step="0.01" name="recommended_rate" x-model.number="rate" class="form-input" required></div>
            </div>
            <div class="grid gap-3 rounded-lg bg-slate-50 p-4 text-sm sm:grid-cols-4">
                <div><div class="text-slate-500">Angsuran</div><div class="font-semibold" x-text="rupiah(inst)"></div></div>
                <div><div class="text-slate-500">DSR</div><div class="font-semibold" :class="dsr > {{ $maxDsr }} ? 'text-rose-600' : 'text-emerald-700'" x-text="dsr.toFixed(2) + '%'"></div></div>
                <div><div class="text-slate-500">LTV</div><div class="font-semibold" :class="ltv > maxLtv ? 'text-rose-600' : 'text-emerald-700'" x-text="ltv === null ? '-' : ltv.toFixed(2) + '%'"></div></div>
                <div><div class="text-slate-500">Skor dasar 5C</div><div class="font-semibold" x-text="base.toFixed(1)"></div></div>
            </div>
            <x-field label="Kesimpulan & rekomendasi analis" name="summary" type="textarea" :value="$an?->summary" required />
            <div class="flex justify-end"><button class="btn-primary">Simpan Analisis</button></div>
        </div>
    </form>
@elseif (! $an)
    <div class="card card-body text-center text-sm text-slate-500">Analisis kredit belum dibuat.</div>
@endif
