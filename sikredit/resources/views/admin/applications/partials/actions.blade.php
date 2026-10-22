@php use App\Enums\ApplicationStatus as S; use App\Enums\Role; @endphp
<div class="card border-2 border-brand-100">
    <div class="card-header bg-brand-50/60"><div class="card-title">Tindakan</div><x-sla :application="$app" /></div>
    <div class="card-body space-y-4 text-sm">

    {{-- CUSTOMER SERVICE: verifikasi dokumen --}}
    @if ($user->hasRole(Role::CustomerService) && $app->status === S::Submitted)
        @php $allValid = $app->documents->isNotEmpty() && $app->documents->every(fn ($d) => $d->status === 'valid') && $app->missingRequiredDocuments()->isEmpty(); @endphp
        <p class="text-slate-600">Periksa keaslian dan kelengkapan dokumen pada tab <strong>Dokumen</strong>, lalu teruskan ke analis.</p>
        <form method="POST" action="{{ route('admin.process.verify', $app) }}" class="space-y-2">
            @csrf
            <x-field label="Tugaskan ke analis" name="analyst_id" :options="$analysts->pluck('name', 'id')" required />
            <textarea name="notes" rows="2" class="form-input" placeholder="Catatan (opsional)"></textarea>
            <button class="btn-success w-full" @disabled(! $allValid)>Dokumen Valid — Teruskan ke Analis</button>
            @unless ($allValid)<p class="text-xs text-amber-700">Semua dokumen wajib harus berstatus Valid.</p>@endunless
        </form>
        <div x-data="{ m: null }" class="space-y-2 border-t border-slate-100 pt-4">
            <div class="flex gap-2"><button type="button" @click="m = 'revisi'" class="btn-warning btn-sm flex-1">Minta Revisi</button><button type="button" @click="m = 'tolak'" class="btn-danger btn-sm flex-1">Tolak</button></div>
            <form x-show="m === 'revisi'" x-cloak method="POST" action="{{ route('admin.process.revision', $app) }}" class="space-y-2">@csrf
                <textarea name="notes" rows="3" class="form-input" placeholder="Jelaskan data/dokumen yang perlu diperbaiki" required></textarea><button class="btn-warning w-full">Kirim Permintaan Revisi</button></form>
            <form x-show="m === 'tolak'" x-cloak method="POST" action="{{ route('admin.process.reject', $app) }}" class="space-y-2">@csrf
                <textarea name="notes" rows="3" class="form-input" placeholder="Alasan penolakan" required></textarea><button class="btn-danger w-full" onclick="return confirm('Tolak pengajuan ini?')">Tolak Pengajuan</button></form>
        </div>

    {{-- ANALIS: survei, appraisal, scoring, rekomendasi --}}
    @elseif ($isAnalyst)
        @php
            $checks = [
                ['Survei lapangan', (bool) $app->survey, 'survei'],
                ['Appraisal agunan', ! $app->product->requires_collateral || ($app->collaterals->isNotEmpty() && $app->collaterals->every(fn ($c) => $c->market_value !== null)), 'agunan'],
                ['Analisis 5C & scoring', (bool) $app->analysis, 'analisis'],
            ];
            $ready = collect($checks)->every(fn ($c) => $c[1]);
        @endphp
        <ul class="space-y-2">
            @foreach ($checks as [$label, $ok, $tab])
                <li class="flex items-center justify-between"><span class="flex items-center gap-2"><span class="{{ $ok ? 'text-emerald-600' : 'text-slate-300' }}">●</span> {{ $label }}</span>
                    <button type="button" @click="tab = '{{ $tab }}'" class="text-xs font-semibold text-brand-700 hover:underline">{{ $ok ? 'Lihat' : 'Isi' }}</button></li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('admin.process.submit-approval', $app) }}" class="space-y-2 border-t border-slate-100 pt-4">
            @csrf
            <label class="form-label">Rekomendasi ke pemutus</label>
            <select name="recommendation" class="form-input"><option value="approve" @selected($app->analysis?->recommendation === 'approve')>Setuju</option><option value="reject" @selected($app->analysis?->recommendation === 'reject')>Tolak</option></select>
            <textarea name="notes" rows="2" class="form-input" placeholder="Catatan untuk pemutus"></textarea>
            <button class="btn-success w-full" @disabled(! $ready)>Ajukan ke Pemutus Kredit</button>
            <p class="text-xs text-slate-500">Pemutus: {{ $app->finalAmount() > (float) \App\Models\Setting::get('limit_kepala_cabang') ? 'Komite Kredit' : 'Kepala Cabang' }} (batas wewenang {{ rupiah(\App\Models\Setting::get('limit_kepala_cabang')) }}).</p>
        </form>
        <form method="POST" action="{{ route('admin.process.revision', $app) }}" x-data="{ open: false }" class="border-t border-slate-100 pt-4">@csrf
            <button type="button" @click="open = !open" class="text-xs font-semibold text-amber-700 hover:underline">Kembalikan ke nasabah untuk revisi</button>
            <div x-show="open" x-cloak class="mt-2 space-y-2"><textarea name="notes" rows="2" class="form-input" required placeholder="Data yang perlu dilengkapi"></textarea><button class="btn-warning btn-sm w-full">Kirim</button></div>
        </form>

    {{-- PEMUTUS: kepala cabang / komite --}}
    @elseif ($app->status === S::Approval && $user->role === $app->requiredApproverRole())
        @if (in_array($user->id, [$app->analyst_id, $app->cs_id]))
            <p class="rounded-lg bg-rose-50 p-3 text-rose-700">Pemisahan tugas: Anda tidak dapat memutus pengajuan yang Anda proses.</p>
        @else
            @if ($an = $app->analysis)
                <div class="rounded-lg bg-slate-50 p-3">Rekomendasi analis: <strong class="{{ $an->recommendation === 'approve' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $an->recommendation === 'approve' ? 'SETUJU' : 'TOLAK' }}</strong> · Grade <strong>{{ $an->grade }}</strong> ({{ $an->total_score }}) · DSR {{ $an->dsr }}%</div>
            @endif
            <form method="POST" action="{{ route('admin.process.decide', $app) }}" x-data="{ d: 'approve' }" class="space-y-3">
                @csrf
                <div class="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1 text-xs font-semibold">
                    @foreach (['approve' => 'Setujui', 'reject' => 'Tolak', 'return' => 'Kembalikan'] as $k => $l)
                        <label class="cursor-pointer rounded-md py-1.5 text-center" :class="d === '{{ $k }}' ? 'bg-white shadow text-slate-900' : 'text-slate-500'"><input type="radio" name="decision" value="{{ $k }}" x-model="d" class="sr-only">{{ $l }}</label>
                    @endforeach
                </div>
                <div x-show="d === 'approve'" class="grid gap-2">
                    <x-field label="Plafon disetujui (Rp)" name="approved_amount" type="number" :value="$app->approved_amount ?? $app->amount" />
                    <div class="grid grid-cols-2 gap-2">
                        <x-field label="Tenor (bln)" name="approved_tenor" type="number" :value="$app->approved_tenor ?? $app->tenor" />
                        <x-field label="Bunga (%)" name="approved_rate" type="number" step="0.01" :value="$app->approved_rate ?? $app->interest_rate" />
                    </div>
                    <x-field label="Syarat & ketentuan tambahan" name="approval_conditions" type="textarea" placeholder="Mis. asuransi jiwa & kebakaran, autodebet gaji" />
                </div>
                <textarea name="notes" rows="3" class="form-input" placeholder="Catatan / dasar keputusan" required></textarea>
                <button class="w-full" :class="d === 'approve' ? 'btn-success' : (d === 'reject' ? 'btn-danger' : 'btn-warning')" onclick="return confirm('Simpan keputusan kredit?')">Simpan Keputusan</button>
            </form>
        @endif

    {{-- LEGAL: pengikatan & akad --}}
    @elseif ($user->hasRole(Role::Legal) && $app->status === S::Approved)
        <form method="POST" action="{{ route('admin.process.legal', $app) }}" class="space-y-3">
            @csrf
            <div class="font-semibold">1. Siapkan perjanjian & jadwal akad</div>
            <x-field label="Jadwal akad" name="akad_scheduled_at" type="datetime-local" :value="$app->akad_scheduled_at?->format('Y-m-d\TH:i')" required />
            <x-field label="Notaris / PPAT" name="notary_name" :value="$app->notary_name" />
            @foreach ($app->collaterals as $c)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="text-xs font-semibold">{{ $c->typeLabel() }} — {{ \Illuminate\Support\Str::limit($c->description, 40) }}</div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <select name="bindings[{{ $c->id }}][binding_type]" class="form-input text-xs"><option value="">Jenis</option>@foreach (\App\Models\Collateral::BINDINGS as $k => $v)<option value="{{ $k }}" @selected($c->binding_type === $k)>{{ $v }}</option>@endforeach</select>
                        <input name="bindings[{{ $c->id }}][binding_number]" value="{{ $c->binding_number }}" placeholder="No. akta/sertifikat" class="form-input text-xs">
                    </div>
                </div>
            @endforeach
            <x-field label="Catatan legal" name="binding_notes" type="textarea" :value="$app->binding_notes" />
            <button class="btn-primary w-full">Simpan & Beri Tahu Nasabah</button>
        </form>
        <form method="POST" action="{{ route('admin.process.contract', $app) }}" class="space-y-2 border-t border-slate-100 pt-4">
            @csrf
            <div class="font-semibold">2. Akad telah ditandatangani</div>
            <x-field label="Tanggal akad" name="akad_date" type="date" :value="today()->toDateString()" required />
            <textarea name="notes" rows="2" class="form-input" placeholder="Catatan"></textarea>
            <button class="btn-success w-full" @disabled(! $app->contract_number)>Akad Selesai — Teruskan ke Pencairan</button>
        </form>

    {{-- TELLER: pencairan --}}
    @elseif ($user->hasRole(Role::Teller) && $app->status === S::Contract)
        <div class="rounded-lg bg-slate-50 p-3">
            <div class="dl-row"><dt>Plafon</dt><dd>{{ rupiah($app->approved_amount) }}</dd></div>
            <div class="dl-row"><dt>Provisi ({{ $app->product->provision_fee }}%)</dt><dd>- {{ rupiah($app->approved_amount * $app->product->provision_fee / 100) }}</dd></div>
            <div class="dl-row"><dt>Biaya admin</dt><dd>- {{ rupiah($app->product->admin_fee) }}</dd></div>
            <div class="dl-row"><dt class="font-semibold text-slate-800">Diterima nasabah</dt><dd class="text-emerald-700">{{ rupiah($app->approved_amount - $app->approved_amount * $app->product->provision_fee / 100 - $app->product->admin_fee) }}</dd></div>
        </div>
        <form method="POST" action="{{ route('admin.process.disburse', $app) }}" class="space-y-2">
            @csrf
            <x-field label="Tanggal pencairan" name="disbursed_at" type="date" :value="today()->toDateString()" required />
            <x-field label="Bank tujuan" name="disbursement_bank" value="SiKredit Bank" required />
            <x-field label="No. rekening tujuan" name="disbursement_account" required inputmode="numeric" />
            <textarea name="notes" rows="2" class="form-input" placeholder="Catatan"></textarea>
            <button class="btn-success w-full" onclick="return confirm('Cairkan dana sekarang? Jadwal angsuran akan dibuat otomatis.')">Cairkan Dana</button>
        </form>

    @else
        @php
            $waiting = match ($app->status) {
                S::Approval => $app->requiredApproverRole()->label(),
                default => collect($app->status->handlerRoles())->map->label()->join(' / '),
            };
        @endphp
        <p class="text-slate-600">
            @if ($app->status->isInProcess() && $waiting) Menunggu tindakan <strong>{{ $waiting }}</strong>.
            @elseif ($app->status === S::Revision) Menunggu nasabah memperbaiki pengajuan.
            @else Tidak ada tindakan yang diperlukan untuk peran Anda. @endif
        </p>
    @endif
    </div>
</div>
