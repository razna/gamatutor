<x-layouts.public title="FAQ">
    @php
    $faqs = [
        ['Siapa saja yang dapat mengajukan kredit?', 'WNI berusia 21–65 tahun, memiliki penghasilan tetap atau usaha yang berjalan minimal 1 tahun, serta tidak memiliki riwayat kredit bermasalah.'],
        ['Berapa lama proses pengajuan?', 'Setiap tahap memiliki SLA: verifikasi dokumen 1 hari kerja, analisis & survei 3 hari kerja, persetujuan 2 hari kerja. Status dapat dipantau real-time di dashboard.'],
        ['Dokumen apa saja yang diperlukan?', 'Umumnya KTP, KK, NPWP, slip gaji/rekening koran, dan dokumen agunan. Daftar lengkap tersedia pada halaman detail setiap produk.'],
        ['Apa perbedaan bunga flat, efektif, dan anuitas?', 'Flat dihitung dari plafon awal; efektif dihitung dari sisa pokok sehingga angsuran menurun; anuitas menghasilkan angsuran tetap dengan porsi bunga menurun.'],
        ['Apakah data saya aman?', 'Ya. Data sensitif seperti NIK dan nomor rekening dienkripsi, dokumen disimpan di penyimpanan privat, dan setiap akses tercatat dalam audit trail.'],
        ['Bagaimana jika pengajuan saya perlu revisi?', 'Anda akan menerima notifikasi beserta catatan petugas. Perbaiki data atau unggah ulang dokumen, lalu kirim kembali pengajuan.'],
        ['Apakah bisa melunasi lebih cepat?', 'Bisa. Gunakan fitur simulasi pelunasan pada menu Pinjaman untuk melihat nominal pelunasan, lalu lakukan pembayaran di teller.'],
        ['Berapa denda keterlambatan?', 'Denda dihitung harian dari angsuran tertunggak sesuai ketentuan produk (umumnya 0,1% per hari).'],
    ];
    @endphp
    <section class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-bold">Pertanyaan yang Sering Diajukan</h1>
        <div class="mt-8 space-y-3">
            @foreach ($faqs as [$q, $a])
                <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="card">
                    <button @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left font-semibold">{{ $q }} <span x-text="open ? '−' : '+'" class="text-xl text-brand-700"></span></button>
                    <div x-show="open" x-cloak class="px-5 pb-5 text-sm text-slate-600">{{ $a }}</div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
