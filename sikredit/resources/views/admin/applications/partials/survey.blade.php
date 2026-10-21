@php $s = $app->survey; @endphp
@if ($s && ! $isAnalyst)
    <div class="card card-body">
        <div class="grid gap-x-8 md:grid-cols-2">
            <dl>
                <div class="dl-row"><dt>Tanggal kunjungan</dt><dd>{{ tanggal($s->visit_date) }}</dd></div>
                <div class="dl-row"><dt>Surveyor</dt><dd>{{ $s->surveyor->name }}</dd></div>
                <div class="dl-row"><dt>Bertemu dengan</dt><dd>{{ $s->met_with ?: '-' }}</dd></div>
                <div class="dl-row"><dt>Kondisi tempat tinggal</dt><dd>{{ ucfirst($s->residence_condition ?? '-') }}</dd></div>
            </dl>
            <dl>
                <div class="dl-row"><dt>Alamat terverifikasi</dt><dd>{{ $s->address_verified ? '✔ Ya' : '✘ Tidak' }}</dd></div>
                <div class="dl-row"><dt>Penghasilan terverifikasi</dt><dd>{{ $s->income_verified ? '✔ Ya' : '✘ Tidak' }}</dd></div>
                <div class="dl-row"><dt>Usaha/pekerjaan terverifikasi</dt><dd>{{ $s->business_verified ? '✔ Ya' : '✘ Tidak' }}</dd></div>
                <div class="dl-row"><dt>Koordinat</dt><dd>@if ($s->latitude)<a class="text-brand-700 hover:underline" target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $s->latitude }},{{ $s->longitude }}">{{ $s->latitude }}, {{ $s->longitude }}</a>@else - @endif</dd></div>
            </dl>
        </div>
        @if ($s->neighborhood_info)<p class="mt-3 text-sm"><span class="font-medium">Info lingkungan:</span> {{ $s->neighborhood_info }}</p>@endif
        @if ($s->notes)<p class="mt-2 text-sm"><span class="font-medium">Catatan:</span> {{ $s->notes }}</p>@endif
        @if ($s->photos)
            <div class="mt-4 grid grid-cols-3 gap-2 md:grid-cols-6">
                @foreach ($s->photos as $i => $p)<a href="{{ route('survey.photo', [$app, $i]) }}" target="_blank"><img src="{{ route('survey.photo', [$app, $i]) }}" class="aspect-square w-full rounded-lg object-cover" alt="Foto survei {{ $i + 1 }}"></a>@endforeach
            </div>
        @endif
    </div>
@elseif ($isAnalyst)
    <form method="POST" action="{{ route('admin.process.survey', $app) }}" enctype="multipart/form-data" class="card card-body grid gap-4 md:grid-cols-3" x-data="{ lat: '{{ old('latitude', $s?->latitude) }}', lng: '{{ old('longitude', $s?->longitude) }}', locate() { navigator.geolocation?.getCurrentPosition(p => { this.lat = p.coords.latitude.toFixed(7); this.lng = p.coords.longitude.toFixed(7); }, () => alert('Lokasi tidak dapat diakses.')); } }">
        @csrf
        <div class="md:col-span-3 card-title">Form Survei Lapangan (On The Spot)</div>
        <x-field label="Tanggal kunjungan" name="visit_date" type="date" :value="$s?->visit_date?->toDateString() ?? today()->toDateString()" required />
        <x-field label="Bertemu dengan" name="met_with" :value="$s?->met_with" placeholder="Pemohon / pasangan / atasan" />
        <x-field label="Kondisi tempat tinggal" name="residence_condition" :value="$s?->residence_condition" :options="['baik' => 'Baik', 'cukup' => 'Cukup', 'kurang' => 'Kurang']" />
        <div class="flex flex-wrap gap-4 md:col-span-3">
            @foreach (['address_verified' => 'Alamat sesuai', 'income_verified' => 'Penghasilan terverifikasi', 'business_verified' => 'Pekerjaan/usaha terverifikasi'] as $f => $l)
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="{{ $f }}" value="0"><input type="checkbox" name="{{ $f }}" value="1" class="rounded border-slate-300 text-brand-600" @checked(old($f, $s?->{$f}))> {{ $l }}</label>
            @endforeach
        </div>
        <x-field class="md:col-span-3" label="Informasi lingkungan / tetangga" name="neighborhood_info" type="textarea" :value="$s?->neighborhood_info" />
        <x-field class="md:col-span-3" label="Catatan survei" name="notes" type="textarea" :value="$s?->notes" />
        <div><label class="form-label">Latitude</label><input name="latitude" x-model="lat" class="form-input"></div>
        <div><label class="form-label">Longitude</label><input name="longitude" x-model="lng" class="form-input"></div>
        <div class="flex items-end"><button type="button" @click="locate()" class="btn-secondary w-full">📍 Ambil lokasi saat ini</button></div>
        <div class="md:col-span-3"><label class="form-label">Foto survei (maks. 6, @ 4 MB)</label><input type="file" name="photos[]" multiple accept="image/*" class="text-sm"></div>
        @if ($s?->photos)<p class="text-xs text-slate-500 md:col-span-3">{{ count($s->photos) }} foto sudah tersimpan; unggahan baru akan ditambahkan.</p>@endif
        <div class="md:col-span-3 flex justify-end"><button class="btn-primary">Simpan Hasil Survei</button></div>
    </form>
@else
    <div class="card card-body text-center text-sm text-slate-500">Survei belum dilakukan.</div>
@endif
