<div class="space-y-4">
    @foreach ($ticket->messages as $m)
        @php $mine = $m->user_id === auth()->id(); @endphp
        <div class="flex {{ $mine ? 'justify-end' : '' }}">
            <div class="max-w-xl rounded-2xl px-4 py-3 text-sm {{ $mine ? 'bg-brand-700 text-white' : 'bg-white border border-slate-200' }}">
                <div class="text-xs {{ $mine ? 'text-brand-100' : 'text-slate-500' }}">{{ $m->user->name }}{{ $m->user->isStaff() ? ' · '.$m->user->role->label() : '' }} · {{ tanggal($m->created_at, true) }}</div>
                <div class="mt-1 whitespace-pre-line">{{ $m->message }}</div>
            </div>
        </div>
    @endforeach
</div>
