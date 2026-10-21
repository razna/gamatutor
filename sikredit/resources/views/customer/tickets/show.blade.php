<x-layouts.app :title="'Tiket '.$ticket->number" :subtitle="$ticket->subject">
    @php [$l, $c] = $ticket->statusBadge(); @endphp
    <div class="mb-4 flex items-center gap-2"><span class="badge {{ $c }}">{{ $l }}</span>
        @if ($ticket->application)<span class="text-sm text-slate-500">Terkait: {{ $ticket->application->number }}</span>@endif</div>
    <div class="max-w-3xl">
        @include('partials.ticket-thread')
        @if ($ticket->status !== 'closed')
            <form method="POST" action="{{ route('customer.tickets.reply', $ticket) }}" class="card card-body mt-6 space-y-3">
                @csrf
                <textarea name="message" rows="3" class="form-input" placeholder="Tulis balasan…" required></textarea>
                <div class="flex justify-end"><button class="btn-primary">Kirim</button></div>
            </form>
        @else
            <p class="mt-6 text-center text-sm text-slate-500">Tiket ini telah ditutup.</p>
        @endif
    </div>
</x-layouts.app>
