<x-layouts.app :title="'Tiket '.$ticket->number" :subtitle="$ticket->user->name.' · '.$ticket->subject">
    @php [$l, $c] = $ticket->statusBadge(); @endphp
    <div class="mb-4 flex flex-wrap items-center gap-2"><span class="badge {{ $c }}">{{ $l }}</span><span class="text-sm text-slate-500">{{ \App\Models\Ticket::CATEGORIES[$ticket->category] }}</span>
        @if ($ticket->application)<a href="{{ route('admin.applications.show', $ticket->application) }}" class="text-sm text-brand-700 hover:underline">{{ $ticket->application->number }}</a>@endif</div>
    <div class="max-w-3xl">
        @include('partials.ticket-thread')
        @if ($ticket->status !== 'closed')
            <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="card card-body mt-6 space-y-3">
                @csrf
                <textarea name="message" rows="4" class="form-input" placeholder="Tulis tanggapan…" required></textarea>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="close" value="1" class="rounded border-slate-300"> Tutup tiket setelah membalas</label>
                    <button class="btn-primary">Kirim Tanggapan</button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
