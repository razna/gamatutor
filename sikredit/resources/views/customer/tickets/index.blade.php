<x-layouts.app title="Pengaduan & Bantuan">
    <x-slot:actions><a href="{{ route('customer.tickets.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> Buat Pengaduan</a></x-slot:actions>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>No. Tiket</th><th>Kategori</th><th>Subjek</th><th>Status</th><th>Diperbarui</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($tickets as $t)
                @php [$l, $c] = $t->statusBadge(); @endphp
                <tr><td class="font-medium">{{ $t->number }}</td><td>{{ \App\Models\Ticket::CATEGORIES[$t->category] }}</td><td class="whitespace-normal">{{ $t->subject }}</td><td><span class="badge {{ $c }}">{{ $l }}</span></td><td>{{ $t->updated_at->locale('id')->diffForHumans() }}</td>
                    <td class="text-right"><a href="{{ route('customer.tickets.show', $t) }}" class="font-semibold text-brand-700 hover:underline">Buka</a></td></tr>
            @empty
                <tr><td colspan="6" class="py-10 text-center text-slate-500">Belum ada pengaduan.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $tickets->links() }}</div>
    </div>
</x-layouts.app>
