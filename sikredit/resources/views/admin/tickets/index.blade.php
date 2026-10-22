<x-layouts.app title="Pengaduan Nasabah">
    <div class="card">
        <div class="flex gap-2 border-b border-slate-100 p-4">
            @foreach (['' => 'Semua', 'open' => 'Menunggu', 'answered' => 'Dijawab', 'closed' => 'Selesai'] as $k => $l)
                <a href="{{ route('admin.tickets.index', array_filter(['status' => $k])) }}" class="btn btn-sm {{ request('status', '') === $k ? 'bg-brand-700 text-white' : 'border border-slate-300 bg-white' }}">{{ $l }}</a>
            @endforeach
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Tiket</th><th>Nasabah</th><th>Kategori</th><th>Subjek</th><th>Status</th><th>Diperbarui</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($tickets as $t)
                    @php [$l, $c] = $t->statusBadge(); @endphp
                    <tr class="cursor-pointer" onclick="location='{{ route('admin.tickets.show', $t) }}'"><td class="font-medium text-brand-700">{{ $t->number }}</td><td>{{ $t->user->name }}</td><td>{{ \App\Models\Ticket::CATEGORIES[$t->category] }}</td><td class="whitespace-normal">{{ $t->subject }}</td><td><span class="badge {{ $c }}">{{ $l }}</span></td><td>{{ $t->updated_at->locale('id')->diffForHumans() }}</td></tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">Tidak ada pengaduan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $tickets->links() }}</div>
    </div>
</x-layouts.app>
