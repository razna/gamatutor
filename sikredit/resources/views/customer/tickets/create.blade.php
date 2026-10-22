<x-layouts.app title="Buat Pengaduan">
    <form method="POST" action="{{ route('customer.tickets.store') }}" class="card card-body grid max-w-3xl gap-4 md:grid-cols-2">
        @csrf
        <x-field label="Kategori" name="category" :options="\App\Models\Ticket::CATEGORIES" required />
        <x-field label="Terkait pengajuan (opsional)" name="loan_application_id" :options="$applications->mapWithKeys(fn ($a) => [$a->id => $a->number.' — '.$a->status->label()])" />
        <x-field class="md:col-span-2" label="Subjek" name="subject" required />
        <x-field class="md:col-span-2" label="Uraian" name="message" type="textarea" required rows="6" />
        <div class="md:col-span-2 flex justify-end"><button class="btn-primary">Kirim Pengaduan</button></div>
    </form>
</x-layouts.app>
