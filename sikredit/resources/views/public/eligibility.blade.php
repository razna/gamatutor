<x-layouts.public title="Cek Kelayakan">
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="text-3xl font-bold">Cek Kelayakan Awal</h1>
        <p class="mt-2 text-slate-500">Pre-screening singkat untuk mengetahui peluang pengajuan Anda. Data tidak disimpan.</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-5">
            <form method="POST" class="card grid h-fit gap-4 p-6 sm:grid-cols-2 lg:col-span-3">
                @csrf
                <x-field class="sm:col-span-2" label="Produk kredit" name="product" :options="$products->pluck('name', 'id')" :value="request('product')" required />
                <x-field label="Usia (tahun)" name="age" type="number" required min="17" />
                <x-field label="Lama bekerja / usaha (tahun)" name="work_years" type="number" required min="0" />
                <x-field label="Penghasilan bersih / bulan (Rp)" name="income" type="number" required min="0" />
                <x-field label="Cicilan berjalan / bulan (Rp)" name="installments" type="number" :value="0" required min="0" />
                <x-field label="Plafon yang diinginkan (Rp)" name="amount" type="number" required min="1000000" />
                <x-field label="Jangka waktu (bulan)" name="tenor" type="number" required min="1" />
                <x-field class="sm:col-span-2" label="Pernah memiliki kredit macet / tunggakan > 90 hari?" name="has_bad_credit" :options="['0' => 'Tidak pernah', '1' => 'Pernah']" required />
                <div class="sm:col-span-2"><button class="btn-primary w-full">Cek Sekarang</button></div>
            </form>

            <div class="lg:col-span-2">
                @if ($result)
                    <div class="card p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full text-2xl font-extrabold {{ $result['eligible'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $result['score'] }}%</div>
                            <div>
                                <div class="text-lg font-bold {{ $result['eligible'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $result['eligible'] ? 'Berpotensi Layak' : 'Perlu Penyesuaian' }}</div>
                                <div class="text-sm text-slate-500">{{ $result['product']->name }}</div>
                            </div>
                        </div>
                        <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm">Estimasi angsuran: <strong>{{ rupiah($result['installment']) }}</strong>/bulan<br>Kemampuan cicilan maksimal Anda: <strong>{{ rupiah($result['max_installment']) }}</strong>/bulan</div>
                        <ul class="mt-4 space-y-2 text-sm">
                            @foreach ($result['checks'] as [$label, $ok])
                                <li class="flex gap-2"><span class="{{ $ok ? 'text-emerald-600' : 'text-rose-600' }}">{{ $ok ? '✔' : '✘' }}</span> {{ $label }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('register') }}" class="btn-primary mt-5 w-full">Lanjutkan Pengajuan</a>
                        <p class="mt-2 text-xs text-slate-500">Hasil ini bukan keputusan kredit. Keputusan final mengikuti analisis dan pemeriksaan SLIK OJK.</p>
                    </div>
                @else
                    <div class="card p-6 text-sm text-slate-600">
                        <div class="font-semibold text-slate-800">Kriteria yang diperiksa</div>
                        <ul class="mt-3 list-inside list-disc space-y-1">
                            <li>Usia 21–65 tahun dan maksimal 70 tahun saat lunas</li>
                            <li>Plafon & tenor sesuai ketentuan produk</li>
                            <li>Rasio cicilan terhadap penghasilan (DSR)</li>
                            <li>Masa kerja / usaha minimal 1 tahun</li>
                            <li>Riwayat kredit (SLIK OJK)</li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.public>
