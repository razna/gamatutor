<x-layouts.public>
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 text-white">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-gold-400/10"></div>
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 lg:grid-cols-2 lg:py-24">
            <div class="relative">
                <span class="badge bg-white/10 text-gold-400">Pengajuan kredit 100% online</span>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight lg:text-5xl">Wujudkan rencana Anda dengan kredit yang <span class="text-gold-400">cepat & transparan</span></h1>
                <p class="mt-5 max-w-xl text-lg text-slate-200">Ajukan KPR, kredit kendaraan, modal usaha, hingga kredit multiguna dari mana saja. Pantau status pengajuan secara real-time hingga dana cair.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn bg-gold-500 px-6 py-3 text-base text-brand-950 hover:bg-gold-400">Ajukan Sekarang</a>
                    <a href="{{ route('simulation') }}" class="btn border border-white/30 px-6 py-3 text-base text-white hover:bg-white/10">Simulasi Kredit</a>
                </div>
                <div class="mt-10 grid max-w-lg grid-cols-3 gap-6 text-sm">
                    <div><div class="text-2xl font-bold">3 hari*</div><div class="text-slate-300">rata-rata keputusan</div></div>
                    <div><div class="text-2xl font-bold">{{ $products->count() }}</div><div class="text-slate-300">produk kredit</div></div>
                    <div><div class="text-2xl font-bold">24/7</div><div class="text-slate-300">pantau status</div></div>
                </div>
            </div>
            <div class="relative" x-data="loanSimulator({ amount: 100000000, tenor: 36, rate: 10.5 })">
                <div class="rounded-2xl bg-white p-6 text-slate-800 shadow-2xl">
                    <div class="flex items-center gap-2 font-semibold"><x-icon name="calc" class="h-5 w-5 text-brand-700" /> Hitung cepat angsuran</div>
                    <div class="mt-5 space-y-5">
                        <div>
                            <div class="flex justify-between text-sm"><span>Plafon pinjaman</span><span class="font-semibold" x-text="rupiah(amount)"></span></div>
                            <input type="range" min="5000000" max="1000000000" step="5000000" x-model="amount" class="mt-2 w-full accent-brand-700">
                        </div>
                        <div>
                            <div class="flex justify-between text-sm"><span>Jangka waktu</span><span class="font-semibold" x-text="tenor + ' bulan'"></span></div>
                            <input type="range" min="6" max="240" step="6" x-model="tenor" class="mt-2 w-full accent-brand-700">
                        </div>
                        <div>
                            <div class="flex justify-between text-sm"><span>Suku bunga (anuitas)</span><span class="font-semibold" x-text="rate + '% p.a.'"></span></div>
                            <input type="range" min="5" max="20" step="0.25" x-model="rate" class="mt-2 w-full accent-brand-700">
                        </div>
                    </div>
                    <div class="mt-6 rounded-xl bg-brand-50 p-4">
                        <div class="text-sm text-slate-600">Estimasi angsuran per bulan</div>
                        <div class="text-3xl font-extrabold text-brand-800" x-text="rupiah(monthly)"></div>
                        <div class="mt-1 text-xs text-slate-500">Total bunga: <span x-text="rupiah(totalInterest)"></span></div>
                    </div>
                    <a href="{{ route('simulation') }}" class="btn-secondary mt-4 w-full">Lihat tabel angsuran lengkap</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-slate-900">Produk Kredit Kami</h2>
            <p class="mt-2 text-slate-500">Pilih produk yang sesuai kebutuhan pribadi maupun usaha Anda.</p>
        </div>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($products as $p)
                <a href="{{ route('products.show', $p) }}" class="card group p-6 transition hover:-translate-y-1 hover:shadow-lg">
                    <span class="badge bg-brand-50 text-brand-700">{{ $p->category }}</span>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900 group-hover:text-brand-700">{{ $p->name }}</h3>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $p->description }}</p>
                    <div class="mt-4 text-sm"><span class="text-slate-500">Bunga mulai</span> <span class="font-bold text-brand-800">{{ number_format($p->interest_rate, 2, ',', '.') }}%</span> <span class="text-slate-500">p.a.</span></div>
                    <div class="text-xs text-slate-500">Plafon hingga {{ rupiah($p->max_amount) }}</div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="bg-white py-16">
        <div class="mx-auto max-w-7xl px-4">
            <h2 class="text-center text-3xl font-bold text-slate-900">Alur Pengajuan</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-5">
                @foreach ([['Daftar & lengkapi profil','Buat akun dan isi data diri, pekerjaan, serta penghasilan.'],['Ajukan & unggah dokumen','Pilih produk, isi plafon & tenor, unggah KTP, slip gaji, dsb.'],['Verifikasi & analisis','CS memverifikasi dokumen, analis melakukan survei & scoring.'],['Persetujuan & akad','Pemutus kredit menyetujui, lalu tanda tangan akad di cabang.'],['Dana cair','Dana dicairkan ke rekening Anda & jadwal angsuran terbit.']] as $i => [$t, $d])
                    <div class="relative rounded-xl border border-slate-200 p-5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-700 font-bold text-white">{{ $i + 1 }}</div>
                        <div class="mt-3 font-semibold">{{ $t }}</div>
                        <p class="mt-1 text-sm text-slate-500">{{ $d }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-16 md:grid-cols-3">
        @foreach ([['shield','Aman & Terlindungi','Data pribadi dienkripsi dan dikelola sesuai UU Pelindungan Data Pribadi.'],['bolt','Proses Cepat','SLA terukur pada setiap tahap dengan notifikasi otomatis.'],['chart','Transparan','Simulasi, biaya, dan jadwal angsuran jelas sejak awal.']] as [$icon, $t, $d])
            <div class="card flex gap-4 p-6">
                <div class="h-fit rounded-lg bg-brand-50 p-3 text-brand-700"><x-icon :name="$icon" class="h-6 w-6" /></div>
                <div><div class="font-semibold">{{ $t }}</div><p class="mt-1 text-sm text-slate-500">{{ $d }}</p></div>
            </div>
        @endforeach
    </section>

    <section class="mx-auto max-w-7xl px-4">
        <div class="flex flex-col items-center justify-between gap-6 rounded-2xl bg-brand-800 px-8 py-10 text-white md:flex-row">
            <div>
                <h3 class="text-2xl font-bold">Belum yakin memenuhi syarat?</h3>
                <p class="mt-1 text-brand-100">Cek kelayakan awal Anda dalam 1 menit, tanpa perlu login.</p>
            </div>
            <a href="{{ route('eligibility') }}" class="btn bg-white px-6 py-3 text-brand-800 hover:bg-brand-50">Cek Kelayakan</a>
        </div>
    </section>
</x-layouts.public>
