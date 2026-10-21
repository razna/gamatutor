@props(['logs', 'showUser' => true])
<ol class="relative space-y-5 border-l border-slate-200 pl-5">
    @forelse ($logs as $log)
        <li class="relative">
            <span class="absolute -left-[27px] top-1 h-3 w-3 rounded-full border-2 border-white {{ $log->sla_breached ? 'bg-rose-500' : 'bg-brand-500' }}"></span>
            <div class="flex flex-wrap items-center gap-2">
                <x-status-badge :status="$log->to_status" />
                <span class="text-xs text-slate-500">{{ tanggal($log->created_at, true) }}</span>
                @if ($log->sla_breached)<span class="badge bg-rose-50 text-rose-700">melewati SLA</span>@endif
            </div>
            @if ($showUser && $log->user)<div class="mt-1 text-xs text-slate-500">oleh {{ $log->user->name }} ({{ $log->user->role->label() }})</div>@endif
            @if ($log->notes)<p class="mt-1 text-sm text-slate-700">{{ $log->notes }}</p>@endif
        </li>
    @empty
        <li class="text-sm text-slate-500">Belum ada riwayat.</li>
    @endforelse
</ol>
