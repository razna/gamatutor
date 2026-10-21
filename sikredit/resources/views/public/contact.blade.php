<x-layouts.public title="Kontak & Cabang">
    <section class="mx-auto max-w-6xl px-4 py-12">
        <h1 class="text-3xl font-bold">Kontak & Lokasi Cabang</h1>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="card p-5"><div class="text-sm text-slate-500">Call Center</div><div class="text-xl font-bold">1500-123</div><div class="text-xs text-slate-500">Senin–Minggu, 24 jam</div></div>
            <div class="card p-5"><div class="text-sm text-slate-500">Email</div><div class="text-xl font-bold">layanan@sikredit.test</div><div class="text-xs text-slate-500">Respons maks. 1×24 jam</div></div>
            <div class="card p-5"><div class="text-sm text-slate-500">Pengaduan</div><div class="text-xl font-bold">Menu Pengaduan</div><div class="text-xs text-slate-500">Tersedia setelah login</div></div>
        </div>
        <h2 class="mt-10 text-xl font-semibold">Kantor Cabang</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($branches as $b)
                <div class="card p-5">
                    <div class="font-semibold">{{ $b->name }}</div>
                    <div class="mt-1 text-sm text-slate-600">{{ $b->address }}, {{ $b->city }}</div>
                    <div class="mt-2 text-sm text-brand-700">{{ $b->phone }}</div>
                    <div class="mt-1 text-xs text-slate-500">Senin–Jumat 08.00–15.00 WIB</div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
