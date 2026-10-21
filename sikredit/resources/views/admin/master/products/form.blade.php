@php $p = $product; $selected = $p->exists ? $p->documentTypes->mapWithKeys(fn ($d) => [$d->id => $d->pivot->is_required ? 'required' : 'optional']) : collect(); @endphp
<x-layouts.app :title="$p->exists ? 'Ubah Produk: '.$p->name : 'Tambah Produk Kredit'">
    <form method="POST" action="{{ $p->exists ? route('admin.products.update', $p) : route('admin.products.store') }}" class="space-y-6">
        @csrf @if ($p->exists) @method('PUT') @endif
        <div class="card card-body grid gap-4 md:grid-cols-4">
            <x-field label="Kode" name="code" :value="$p->code" required />
            <x-field class="md:col-span-2" label="Nama produk" name="name" :value="$p->name" required />
            <x-field label="Kategori" name="category" :value="$p->category" :options="\App\Models\CreditProduct::CATEGORIES" required />
            <x-field class="md:col-span-4" label="Deskripsi" name="description" type="textarea" :value="$p->description" />
            <x-field class="md:col-span-4" label="Persyaratan umum" name="requirements" type="textarea" :value="$p->requirements" />
            <x-field label="Suku bunga (% p.a.)" name="interest_rate" type="number" step="0.01" :value="$p->interest_rate" required />
            <x-field label="Metode bunga" name="interest_method" :value="$p->interest_method?->value" :options="collect(\App\Enums\InterestMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])" required />
            <x-field label="Plafon minimum" name="min_amount" type="number" :value="$p->min_amount" required />
            <x-field label="Plafon maksimum" name="max_amount" type="number" :value="$p->max_amount" required />
            <x-field label="Tenor minimum (bln)" name="min_tenor" type="number" :value="$p->min_tenor" required />
            <x-field label="Tenor maksimum (bln)" name="max_tenor" type="number" :value="$p->max_tenor" required />
            <x-field label="Provisi (%)" name="provision_fee" type="number" step="0.01" :value="$p->provision_fee" required />
            <x-field label="Biaya admin (Rp)" name="admin_fee" type="number" :value="$p->admin_fee ?? 0" required />
            <x-field label="Denda (% per hari)" name="penalty_rate" type="number" step="0.001" :value="$p->penalty_rate" required />
            <x-field label="LTV maksimum (%)" name="max_ltv" type="number" step="0.01" :value="$p->max_ltv" required />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="requires_collateral" value="1" class="rounded border-slate-300" @checked(old('requires_collateral', $p->requires_collateral))> Wajib agunan</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $p->is_active))> Produk aktif</label>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-title">Dokumen Persyaratan</div></div>
            <div class="card-body grid gap-3 md:grid-cols-2">
                @foreach ($documentTypes as $d)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span>{{ $d->name }}</span>
                        <select name="documents[{{ $d->id }}]" class="form-input w-36 py-1 text-xs">
                            @foreach (['' => 'Tidak perlu', 'required' => 'Wajib', 'optional' => 'Opsional'] as $k => $v)<option value="{{ $k }}" @selected(($selected[$d->id] ?? '') === $k)>{{ $v }}</option>@endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="flex justify-end gap-2"><a href="{{ route('admin.products.index') }}" class="btn-secondary">Batal</a><button class="btn-primary">Simpan</button></div>
    </form>
</x-layouts.app>
