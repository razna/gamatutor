@php use App\Enums\ApplicationStatus as S; $a = $application; $docs = $a->documents->keyBy('document_type_id'); @endphp
<x-layouts.app :title="'Pengajuan '.$a->number" :subtitle="$a->product->name.' · '.$a->branch->name">
    <x-slot:actions>
        @if ($a->status->isEditableByCustomer())<a href="{{ route('customer.applications.edit', $a) }}" class="btn-secondary btn-sm">Ubah Data</a>@endif
    </x-slot:actions>

    <div class="card card-body mb-6"><x-timeline :application="$a" /></div>

    @if ($a->status === S::Revision && ($last = $a->logs->firstWhere('to_status', S::Revision)))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <div class="font-semibold">Pengajuan perlu diperbaiki</div>
            <p class="mt-1">{{ $last->notes }}</p>
            <p class="mt-2 text-xs">Perbaiki data/dokumen sesuai catatan, lalu kirim ulang pengajuan.</p>
        </div>
    @endif
    @if ($a->status === S::Approved || $a->status === S::Contract)
        <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 p-5 text-sm text-teal-900">
            <div class="font-semibold">Selamat! Kredit Anda disetujui.</div>
            <p class="mt-1">Plafon {{ rupiah($a->approved_amount) }}, tenor {{ $a->approved_tenor }} bulan, bunga {{ $a->approved_rate }}% p.a.
                @if ($a->akad_scheduled_at) Akad dijadwalkan <strong>{{ tanggal($a->akad_scheduled_at, true) }}</strong> di {{ $a->branch->name }}. @endif</p>
            @if ($a->approval_conditions)<p class="mt-1">Syarat: {{ $a->approval_conditions }}</p>@endif
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('print.sp3k', $a) }}" target="_blank" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4" /> Unduh SP3K</a>
                @if ($a->contract_number)<a href="{{ route('print.contract', $a) }}" target="_blank" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4" /> Draf Perjanjian Kredit</a>@endif
            </div>
        </div>
    @endif
    @if ($a->status === S::Rejected)
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-900">
            <div class="font-semibold">Mohon maaf, pengajuan Anda belum dapat disetujui.</div>
            <p class="mt-1">{{ $a->decision_notes }}</p>
        </div>
    @endif
    @if (in_array($a->status, [S::Active, S::PaidOff]))
        <div class="mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
            <div><div class="font-semibold">Dana telah dicairkan pada {{ tanggal($a->disbursed_at) }}.</div><div>Lihat jadwal angsuran dan riwayat pembayaran Anda.</div></div>
            <a href="{{ route('customer.loans.show', $a) }}" class="btn-success btn-sm">Lihat Pinjaman</a>
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><div class="card-title">Ringkasan Pengajuan</div><x-status-badge :status="$a->status" /></div>
                <div class="card-body grid gap-x-8 md:grid-cols-2">
                    <dl>
                        <div class="dl-row"><dt>Produk</dt><dd>{{ $a->product->name }}</dd></div>
                        <div class="dl-row"><dt>Plafon diajukan</dt><dd>{{ rupiah($a->amount) }}</dd></div>
                        <div class="dl-row"><dt>Jangka waktu</dt><dd>{{ $a->tenor }} bulan</dd></div>
                        <div class="dl-row"><dt>Suku bunga</dt><dd>{{ $a->finalRate() }}% p.a. ({{ $a->interest_method->label() }})</dd></div>
                    </dl>
                    <dl>
                        <div class="dl-row"><dt>Tujuan</dt><dd>{{ \App\Models\LoanApplication::PURPOSES[$a->purpose] ?? $a->purpose }}</dd></div>
                        <div class="dl-row"><dt>Estimasi angsuran</dt><dd>{{ rupiah($simulation['max_installment']) }}</dd></div>
                        <div class="dl-row"><dt>Tanggal pengajuan</dt><dd>{{ tanggal($a->submitted_at) }}</dd></div>
                        <div class="dl-row"><dt>Cabang</dt><dd>{{ $a->branch->name }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title">Dokumen Persyaratan</div>
                    <span class="text-xs text-slate-500">PDF/JPG/PNG, maks. 5 MB</span></div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($a->product->documentTypes as $type)
                        @php $doc = $docs[$type->id] ?? null; @endphp
                        <li class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center">
                            <div class="flex-1">
                                <div class="font-medium">{{ $type->name }} @if ($type->pivot->is_required)<span class="text-rose-500">*</span>@else<span class="text-xs text-slate-400">(opsional)</span>@endif</div>
                                @if ($doc)
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                        <a href="{{ route('documents.show', $doc) }}" target="_blank" class="text-brand-700 hover:underline">{{ $doc->original_name }}</a>
                                        @php [$l, $c] = $doc->statusBadge(); @endphp<span class="badge {{ $c }}">{{ $l }}</span>
                                        @if ($doc->note)<span class="text-rose-600">— {{ $doc->note }}</span>@endif
                                    </div>
                                @else
                                    <div class="mt-1 text-xs text-slate-400">Belum diunggah</div>
                                @endif
                            </div>
                            @if ($a->status->isEditableByCustomer())
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('customer.applications.documents.store', $a) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="document_type_id" value="{{ $type->id }}">
                                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="block w-52 text-xs file:mr-2 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-brand-700">
                                        <button class="btn-primary btn-sm">{{ $doc ? 'Ganti' : 'Unggah' }}</button>
                                    </form>
                                    @if ($doc)
                                        <form method="POST" action="{{ route('customer.applications.documents.destroy', [$a, $doc]) }}" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('DELETE')<button class="btn btn-sm text-rose-600 hover:bg-rose-50">Hapus</button></form>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @error('documents')<p class="form-error px-5 pb-4">{{ $message }}</p>@enderror
            </div>

            @if ($a->collaterals->isNotEmpty())
                <div class="card">
                    <div class="card-header"><div class="card-title">Agunan</div></div>
                    <div class="overflow-x-auto"><table class="table">
                        <thead><tr><th>Jenis</th><th>Deskripsi</th><th>No. Dokumen</th><th class="text-right">Taksiran</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">@foreach ($a->collaterals as $c)<tr><td>{{ $c->typeLabel() }}</td><td class="whitespace-normal">{{ $c->description }}</td><td>{{ $c->ownership_number ?: '-' }}</td><td class="text-right">{{ rupiah($c->estimated_value) }}</td></tr>@endforeach</tbody>
                    </table></div>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if ($a->status->isEditableByCustomer())
                <form method="POST" action="{{ route('customer.applications.submit', $a) }}" class="card card-body space-y-3">
                    @csrf
                    <div class="card-title">Kirim Pengajuan</div>
                    @php $missing = $a->missingRequiredDocuments(); @endphp
                    @if ($missing->isNotEmpty())
                        <div class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Dokumen wajib belum lengkap: {{ $missing->pluck('name')->join(', ') }}</div>
                    @endif
                    <label class="flex items-start gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="agreement" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600">
                        Saya menyatakan data dan dokumen yang saya berikan benar, serta menyetujui Bank melakukan pemeriksaan riwayat kredit (SLIK OJK) dan survei.
                    </label>
                    @error('agreement')<p class="form-error">{{ $message }}</p>@enderror
                    <button class="btn-success w-full" @disabled($missing->isNotEmpty())>{{ $a->status === S::Revision ? 'Kirim Ulang Pengajuan' : 'Kirim Pengajuan' }}</button>
                </form>
            @endif

            <div class="card">
                <div class="card-header"><div class="card-title">Riwayat Status</div></div>
                <div class="card-body"><x-log-list :logs="$a->logs" :show-user="false" /></div>
            </div>

            @if ($a->status->isCancellable())
                <form method="POST" action="{{ route('customer.applications.cancel', $a) }}" x-data="{ open: false }" class="card card-body">
                    @csrf
                    <button type="button" @click="open = !open" class="text-sm font-medium text-rose-600 hover:underline">Batalkan pengajuan</button>
                    <div x-show="open" x-cloak class="mt-3 space-y-2">
                        <textarea name="reason" class="form-input" rows="2" placeholder="Alasan pembatalan" required></textarea>
                        <button class="btn-danger btn-sm w-full" onclick="return confirm('Yakin membatalkan pengajuan ini?')">Konfirmasi Pembatalan</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
