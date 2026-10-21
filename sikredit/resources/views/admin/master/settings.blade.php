<x-layouts.app title="Pengaturan Sistem" subtitle="Parameter scoring, kebijakan kredit, SLA, dan keamanan">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6" x-data="{ w: { @foreach (['character','capacity','capital','collateral','condition'] as $k) {{ $k }}: {{ (float) old('weight_'.$k, $values['weight_'.$k]) }}, @endforeach } }">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header"><div class="card-title">Bobot Scoring 5C</div><span class="text-sm" :class="Object.values(w).reduce((a, b) => a + Number(b), 0) == 100 ? 'text-emerald-600' : 'text-rose-600'" x-text="'Total: ' + Object.values(w).reduce((a, b) => a + Number(b), 0)"></span></div>
            <div class="card-body grid gap-4 md:grid-cols-5">
                @foreach (['character' => 'Character', 'capacity' => 'Capacity', 'capital' => 'Capital', 'collateral' => 'Collateral', 'condition' => 'Condition'] as $k => $l)
                    <div><label class="form-label">{{ $l }} (%)</label><input type="number" name="weight_{{ $k }}" x-model.number="w.{{ $k }}" class="form-input" min="0" max="100"></div>
                @endforeach
            </div>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card">
                <div class="card-header"><div class="card-title">Kebijakan Kredit</div></div>
                <div class="card-body grid gap-4 md:grid-cols-2">
                    <x-field label="DSR maksimum (%)" name="max_dsr" type="number" :value="$values['max_dsr']" required />
                    <x-field label="Batas wewenang Kepala Cabang (Rp)" name="limit_kepala_cabang" type="number" :value="$values['limit_kepala_cabang']" required hint="Di atas nilai ini diputus Komite Kredit." />
                    <x-field label="Biaya pelunasan dipercepat (%)" name="early_payoff_penalty" type="number" step="0.01" :value="$values['early_payoff_penalty']" required />
                    <x-field label="Pengingat sebelum jatuh tempo (hari)" name="reminder_days_before" type="number" :value="$values['reminder_days_before']" required />
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title">SLA per Tahap (jam kerja)</div></div>
                <div class="card-body grid gap-4 md:grid-cols-3">
                    @foreach (['sla_submitted' => 'Verifikasi dokumen', 'sla_analysis' => 'Analisis & survei', 'sla_approval' => 'Persetujuan', 'sla_approved' => 'Legal & akad', 'sla_contract' => 'Pencairan'] as $k => $l)
                        <x-field :label="$l" :name="$k" type="number" :value="$values[$k]" required />
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card card-body">
            <div class="card-title">Keamanan</div>
            <x-field class="mt-3 max-w-md" label="Verifikasi dua langkah (OTP email) untuk petugas" name="staff_two_factor" :value="(string) (int) $values['staff_two_factor']" :options="['0' => 'Nonaktif', '1' => 'Aktif']" required />
        </div>
        <div class="flex justify-end"><button class="btn-primary px-8">Simpan Pengaturan</button></div>
    </form>

    <form method="POST" action="{{ route('admin.settings.templates') }}" class="card mt-6">
        @csrf @method('PUT')
        <div class="card-header"><div class="card-title">Template Notifikasi</div><span class="text-xs text-slate-500">Placeholder: {nama} {nomor} {status} {catatan} {jumlah} {jatuh_tempo} {angsuran_ke} {kuitansi}</span></div>
        <div class="card-body space-y-5">
            @foreach (\App\Models\NotificationTemplate::EVENTS as $event => $label)
                <div class="grid gap-3 md:grid-cols-3">
                    <div class="text-sm font-medium">{{ $label }}</div>
                    <div class="space-y-2 md:col-span-2">
                        <input name="templates[{{ $event }}][subject]" value="{{ $templates[$event]->subject ?? '' }}" class="form-input" placeholder="Subjek" required>
                        <textarea name="templates[{{ $event }}][body]" rows="2" class="form-input" placeholder="Isi pesan" required>{{ $templates[$event]->body ?? '' }}</textarea>
                    </div>
                </div>
            @endforeach
            <div class="flex justify-end"><button class="btn-primary">Simpan Template</button></div>
        </div>
    </form>
</x-layouts.app>
