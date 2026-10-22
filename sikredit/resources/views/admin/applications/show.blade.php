@php
    use App\Enums\ApplicationStatus as S;
    use App\Enums\Role;
    $isAnalyst = $user->hasRole(Role::Analis) && $app->status === S::Analysis && (! $app->analyst_id || $app->analyst_id === $user->id);
    $tabs = ['ringkasan' => 'Ringkasan', 'nasabah' => 'Data Nasabah', 'dokumen' => 'Dokumen ('.$app->documents->count().')', 'agunan' => 'Agunan ('.$app->collaterals->count().')', 'survei' => 'Survei', 'analisis' => 'Analisis 5C', 'riwayat' => 'Riwayat'];
    $defaultTab = match (true) { $app->status === S::Submitted => 'dokumen', $isAnalyst => 'analisis', default => 'ringkasan' };
@endphp
<x-layouts.app :title="'Pengajuan '.$app->number" :subtitle="$customer->user->name.' · '.$app->product->name.' · '.$app->branch->name">
    <x-slot:actions>
        <x-status-badge :status="$app->status" class="hidden sm:inline-flex" />
        @if (in_array($app->status, [S::Active, S::PaidOff]))<a href="{{ route('admin.loans.show', $app) }}" class="btn-success btn-sm">Lihat Pinjaman</a>@endif
    </x-slot:actions>

    <div class="card card-body mb-6"><x-timeline :application="$app" /></div>

    <div class="grid items-start gap-6 xl:grid-cols-3" x-data="{ tab: '{{ request('tab', $defaultTab) }}' }">
        <div class="min-w-0 space-y-6 xl:col-span-2">
            <div class="flex gap-1 overflow-x-auto rounded-xl bg-white p-1 shadow-sm">
                @foreach ($tabs as $k => $label)
                    <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'bg-brand-700 text-white' : 'text-slate-600 hover:bg-slate-100'" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium">{{ $label }}</button>
                @endforeach
            </div>

            {{-- Ringkasan --}}
            <div x-show="tab === 'ringkasan'" class="space-y-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <x-stat label="Plafon" :value="rupiah_singkat($app->finalAmount())" :hint="rupiah($app->finalAmount()).($app->approved_amount && $app->approved_amount != $app->amount ? ' (diajukan '.rupiah_singkat($app->amount).')' : '')" icon="wallet" />
                    <x-stat label="Angsuran" :value="rupiah_singkat($simulation['max_installment'])" :hint="rupiah($simulation['max_installment']).' · '.$app->finalTenor().' bln · '.$app->finalRate().'%'" icon="calc" tone="violet" />
                    <x-stat label="DSR" :value="number_format($dsr, 1, ',', '.').'%'" :hint="'Batas '.\App\Models\Setting::get('max_dsr').'%'" icon="chart" :tone="$dsr > \App\Models\Setting::get('max_dsr') ? 'rose' : 'green'" />
                    <x-stat label="LTV" :value="$ltv !== null ? number_format($ltv, 1, ',', '.').'%' : '-'" :hint="'Maks '.$app->product->max_ltv.'%'" icon="shield" :tone="$ltv !== null && $ltv > $app->product->max_ltv ? 'rose' : 'green'" />
                </div>
                <div class="card">
                    <div class="card-header"><div class="card-title">Detail Pengajuan</div></div>
                    <div class="card-body grid gap-x-8 md:grid-cols-2">
                        <dl>
                            <div class="dl-row"><dt>Produk</dt><dd>{{ $app->product->name }}</dd></div>
                            <div class="dl-row"><dt>Metode bunga</dt><dd>{{ $app->interest_method->label() }}</dd></div>
                            <div class="dl-row"><dt>Plafon diajukan</dt><dd>{{ rupiah($app->amount) }} / {{ $app->tenor }} bln</dd></div>
                            <div class="dl-row"><dt>Tujuan</dt><dd>{{ \App\Models\LoanApplication::PURPOSES[$app->purpose] ?? $app->purpose }}</dd></div>
                            <div class="dl-row"><dt>Total bunga</dt><dd>{{ rupiah($simulation['total_interest']) }}</dd></div>
                            <div class="dl-row"><dt>Provisi + admin</dt><dd>{{ rupiah($simulation['provision'] + $simulation['admin_fee']) }}</dd></div>
                        </dl>
                        <dl>
                            <div class="dl-row"><dt>Diajukan</dt><dd>{{ tanggal($app->submitted_at, true) }}</dd></div>
                            <div class="dl-row"><dt>Customer Service</dt><dd>{{ $app->cs?->name ?? '-' }}</dd></div>
                            <div class="dl-row"><dt>Analis</dt><dd>{{ $app->analyst?->name ?? '-' }}</dd></div>
                            <div class="dl-row"><dt>Pemutus</dt><dd>{{ $app->approver?->name ?? ($app->required_approver_role ? Role::from($app->required_approver_role)->label() : '-') }}</dd></div>
                            <div class="dl-row"><dt>No. SP3K / Perjanjian</dt><dd>{{ $app->sp3k_number ?? '-' }} / {{ $app->contract_number ?? '-' }}</dd></div>
                            <div class="dl-row"><dt>SLA tahap ini</dt><dd>{{ $app->sla_due_at ? tanggal($app->sla_due_at, true) : '-' }} <x-sla :application="$app" /></dd></div>
                        </dl>
                    </div>
                    @if ($app->purpose_detail)<div class="border-t border-slate-100 px-5 py-3 text-sm text-slate-600"><span class="font-medium">Keterangan:</span> {{ $app->purpose_detail }}</div>@endif
                </div>
                @if ($app->decision_notes)
                    <div class="card card-body text-sm"><div class="card-title">Keputusan Kredit</div>
                        <p class="mt-2">{{ $app->decision_notes }}</p>
                        @if ($app->approval_conditions)<p class="mt-2"><span class="font-medium">Syarat:</span> {{ $app->approval_conditions }}</p>@endif
                        <p class="mt-2 text-xs text-slate-500">{{ $app->approver?->name }} · {{ tanggal($app->decided_at, true) }}</p>
                    </div>
                @endif
                @if ($history->isNotEmpty())
                    <div class="card">
                        <div class="card-header"><div class="card-title">Riwayat Kredit Nasabah di Bank</div></div>
                        <div class="overflow-x-auto"><table class="table"><thead><tr><th>No.</th><th>Produk</th><th class="text-right">Plafon</th><th>Status</th><th>Kolektibilitas</th></tr></thead><tbody class="divide-y divide-slate-100">
                            @foreach ($history as $h)<tr><td><a class="text-brand-700" href="{{ route('admin.applications.show', $h) }}">{{ $h->number }}</a></td><td>{{ $h->product->name }}</td><td class="text-right">{{ rupiah($h->finalAmount()) }}</td><td><x-status-badge :status="$h->status" /></td><td>{{ $h->collectibilityEnum()?->label() ?? '-' }}</td></tr>@endforeach
                        </tbody></table></div>
                    </div>
                @endif
            </div>

            {{-- Data nasabah --}}
            <div x-show="tab === 'nasabah'" x-cloak class="card">
                <div class="card-body grid gap-x-8 md:grid-cols-2">
                    <dl>
                        <div class="dl-row"><dt>Nama</dt><dd>{{ $customer->user->name }}</dd></div>
                        <div class="dl-row"><dt>NIK</dt><dd>{{ $customer->nik }}</dd></div>
                        <div class="dl-row"><dt>No. KK / NPWP</dt><dd>{{ $customer->kk_number ?: '-' }} / {{ $customer->npwp ?: '-' }}</dd></div>
                        <div class="dl-row"><dt>TTL / Usia</dt><dd>{{ $customer->birth_place }}, {{ tanggal($customer->birth_date) }} ({{ $customer->age() }} th)</dd></div>
                        <div class="dl-row"><dt>Jenis kelamin</dt><dd>{{ $customer->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd></div>
                        <div class="dl-row"><dt>Status / Tanggungan</dt><dd>{{ ucfirst($customer->marital_status) }} / {{ $customer->dependents }}</dd></div>
                        <div class="dl-row"><dt>Ibu kandung</dt><dd>{{ $customer->mother_name }}</dd></div>
                        <div class="dl-row"><dt>Alamat</dt><dd class="whitespace-normal">{{ $customer->address }}, {{ $customer->city }} {{ $customer->postal_code }}</dd></div>
                        <div class="dl-row"><dt>Status rumah</dt><dd>{{ str_replace('_', ' ', $customer->home_status) }}</dd></div>
                        <div class="dl-row"><dt>Kontak</dt><dd>{{ $customer->user->phone }} · {{ $customer->user->email }}</dd></div>
                    </dl>
                    <dl>
                        <div class="dl-row"><dt>Pekerjaan</dt><dd>{{ \App\Models\Customer::OCCUPATIONS[$customer->occupation_type] ?? '-' }}</dd></div>
                        <div class="dl-row"><dt>Perusahaan / usaha</dt><dd>{{ $customer->employer_name }}</dd></div>
                        <div class="dl-row"><dt>Jabatan / lama kerja</dt><dd>{{ $customer->job_title ?: '-' }} / {{ $customer->work_years }} th</dd></div>
                        <div class="dl-row"><dt>Penghasilan utama</dt><dd>{{ rupiah($customer->monthly_income) }}</dd></div>
                        <div class="dl-row"><dt>Penghasilan lain</dt><dd>{{ rupiah($customer->other_income) }}</dd></div>
                        <div class="dl-row"><dt>Penghasilan pasangan</dt><dd>{{ rupiah($customer->spouse_income) }}</dd></div>
                        <div class="dl-row"><dt>Total penghasilan</dt><dd class="text-brand-800">{{ rupiah($netIncome) }}</dd></div>
                        <div class="dl-row"><dt>Pengeluaran rutin</dt><dd>{{ rupiah($customer->monthly_expense) }}</dd></div>
                        <div class="dl-row"><dt>Cicilan berjalan</dt><dd>{{ rupiah($customer->existing_installments) }}</dd></div>
                        <div class="dl-row"><dt>Pasangan</dt><dd>{{ $customer->spouse_name ?: '-' }}{{ $customer->spouse_occupation ? ' ('.$customer->spouse_occupation.')' : '' }}</dd></div>
                        <div class="dl-row"><dt>Kontak darurat</dt><dd>{{ $customer->emergency_name }} ({{ $customer->emergency_relation }}) {{ $customer->emergency_phone }}</dd></div>
                    </dl>
                </div>
            </div>

            {{-- Dokumen --}}
            <div x-show="tab === 'dokumen'" x-cloak class="card">
                <ul class="divide-y divide-slate-100">
                    @forelse ($app->documents as $doc)
                        @php [$l, $c] = $doc->statusBadge(); @endphp
                        <li class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                            <div class="flex-1">
                                <div class="font-medium">{{ $doc->type->name }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                    <a href="{{ route('documents.show', $doc) }}" target="_blank" class="text-brand-700 hover:underline">{{ $doc->original_name }}</a>
                                    <span class="text-slate-400">{{ number_format($doc->size / 1024, 0) }} KB</span>
                                    <span class="badge {{ $c }}">{{ $l }}</span>
                                    @if ($doc->verifier)<span class="text-slate-400">oleh {{ $doc->verifier->name }}</span>@endif
                                    @if ($doc->note)<span class="text-rose-600">— {{ $doc->note }}</span>@endif
                                </div>
                            </div>
                            @if ($user->hasRole(Role::CustomerService) && $app->status === S::Submitted)
                                <form method="POST" action="{{ route('admin.process.document', [$app, $doc]) }}" x-data="{ s: '{{ $doc->status === 'invalid' ? 'invalid' : 'valid' }}' }" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <select name="status" x-model="s" class="form-input w-32 py-1.5 text-xs"><option value="valid">Valid</option><option value="invalid">Tidak valid</option></select>
                                    <input x-show="s === 'invalid'" name="note" placeholder="Alasan" class="form-input w-40 py-1.5 text-xs" value="{{ $doc->note }}">
                                    <button class="btn-primary btn-sm">Simpan</button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">Belum ada dokumen.</li>
                    @endforelse
                </ul>
                @if (($missing = $app->missingRequiredDocuments())->isNotEmpty())
                    <div class="border-t border-slate-100 bg-amber-50 px-5 py-3 text-sm text-amber-800">Dokumen wajib belum ada: {{ $missing->pluck('name')->join(', ') }}</div>
                @endif
            </div>

            {{-- Agunan --}}
            <div x-show="tab === 'agunan'" x-cloak class="space-y-4">
                @forelse ($app->collaterals as $c)
                    <div class="card card-body">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div><div class="font-semibold">{{ $c->typeLabel() }}</div><div class="text-sm text-slate-600">{{ $c->description }}</div>
                                <div class="mt-1 text-xs text-slate-500">No. {{ $c->ownership_number ?: '-' }} · a.n. {{ $c->owner_name ?: '-' }} · {{ $c->location ?: '-' }}</div></div>
                            <div class="text-right text-sm"><div class="text-slate-500">Taksiran nasabah</div><div class="font-semibold">{{ rupiah($c->estimated_value) }}</div></div>
                        </div>
                        <div class="mt-3 grid gap-3 rounded-lg bg-slate-50 p-3 text-sm sm:grid-cols-4">
                            <div><div class="text-slate-500">Nilai pasar</div><div class="font-semibold">{{ $c->market_value ? rupiah($c->market_value) : '-' }}</div></div>
                            <div><div class="text-slate-500">Nilai likuidasi</div><div class="font-semibold">{{ $c->liquidation_value ? rupiah($c->liquidation_value) : '-' }}</div></div>
                            <div><div class="text-slate-500">Pengikatan</div><div class="font-semibold">{{ $c->binding_type ? \App\Models\Collateral::BINDINGS[$c->binding_type] : '-' }} {{ $c->binding_number }}</div></div>
                            <div><div class="text-slate-500">Appraiser</div><div class="font-semibold">{{ $c->appraiser?->name ?? '-' }}</div></div>
                        </div>
                        @if ($isAnalyst)
                            <form method="POST" action="{{ route('admin.process.appraise', [$app, $c]) }}" class="mt-3 grid gap-3 sm:grid-cols-4">
                                @csrf
                                <input type="number" name="market_value" min="0" value="{{ $c->market_value }}" placeholder="Nilai pasar" class="form-input" required>
                                <input type="number" name="liquidation_value" min="0" value="{{ $c->liquidation_value }}" placeholder="Nilai likuidasi" class="form-input" required>
                                <select name="binding_type" class="form-input"><option value="">Jenis pengikatan</option>@foreach (\App\Models\Collateral::BINDINGS as $k => $v)<option value="{{ $k }}" @selected($c->binding_type === $k)>{{ $v }}</option>@endforeach</select>
                                <button class="btn-primary">Simpan Appraisal</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="card card-body text-center text-sm text-slate-500">Tanpa agunan.</div>
                @endforelse
            </div>

            {{-- Survei --}}
            <div x-show="tab === 'survei'" x-cloak>@include('admin.applications.partials.survey')</div>

            {{-- Analisis --}}
            <div x-show="tab === 'analisis'" x-cloak>@include('admin.applications.partials.analysis')</div>

            {{-- Riwayat --}}
            <div x-show="tab === 'riwayat'" x-cloak class="card card-body"><x-log-list :logs="$app->logs" /></div>
        </div>

        <div class="space-y-6">
            @include('admin.applications.partials.actions')
            <div class="card card-body text-sm">
                <div class="card-title">Dokumen Cetak</div>
                <div class="mt-3 flex flex-col gap-2">
                    @if ($app->analysis)<a href="{{ route('print.memo', $app) }}" target="_blank" class="btn-secondary btn-sm justify-start"><x-icon name="print" class="h-4 w-4" /> Memo Analisis Kredit</a>@endif
                    @if ($app->sp3k_number)<a href="{{ route('print.sp3k', $app) }}" target="_blank" class="btn-secondary btn-sm justify-start"><x-icon name="print" class="h-4 w-4" /> SP3K</a>@endif
                    @if ($app->contract_number)<a href="{{ route('print.contract', $app) }}" target="_blank" class="btn-secondary btn-sm justify-start"><x-icon name="print" class="h-4 w-4" /> Perjanjian Kredit</a>@endif
                    @if (! $app->analysis && ! $app->sp3k_number)<p class="text-xs text-slate-500">Dokumen tersedia setelah analisis/persetujuan.</p>@endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title">Riwayat Terakhir</div></div>
                <div class="card-body"><x-log-list :logs="$app->logs->take(4)" /></div>
            </div>
        </div>
    </div>
</x-layouts.app>
