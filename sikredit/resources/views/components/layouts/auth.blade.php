@props(['title' => null])
<x-layouts.public :title="$title">
    <section class="mx-auto flex max-w-6xl items-center gap-12 px-4 py-12 lg:py-20">
        <div class="hidden flex-1 lg:block">
            <h2 class="text-3xl font-extrabold text-brand-900">Layanan kredit dalam genggaman.</h2>
            <ul class="mt-6 space-y-4 text-slate-600">
                <li class="flex gap-3"><x-icon name="check" class="h-6 w-6 text-emerald-600" /> Ajukan dan unggah dokumen secara online</li>
                <li class="flex gap-3"><x-icon name="check" class="h-6 w-6 text-emerald-600" /> Pantau status pengajuan secara real-time</li>
                <li class="flex gap-3"><x-icon name="check" class="h-6 w-6 text-emerald-600" /> Lihat jadwal angsuran & riwayat pembayaran</li>
                <li class="flex gap-3"><x-icon name="shield" class="h-6 w-6 text-brand-600" /> Data terenkripsi & diawasi OJK</li>
            </ul>
        </div>
        <div class="card w-full max-w-md p-8">
            {{ $slot }}
        </div>
    </section>
</x-layouts.public>
