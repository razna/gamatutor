@props(['application'])
@if ($application->sla_due_at && $application->status->slaKey())
    @if ($application->isSlaBreached())
        <span class="badge bg-rose-100 text-rose-700" title="Batas SLA {{ tanggal($application->sla_due_at, true) }}">Lewat SLA {{ $application->sla_due_at->locale('id')->diffForHumans(null, true) }}</span>
    @elseif ($application->sla_due_at->diffInHours(now(), true) < 8)
        <span class="badge bg-amber-100 text-amber-700">SLA {{ $application->sla_due_at->locale('id')->diffForHumans() }}</span>
    @else
        <span class="badge bg-slate-100 text-slate-600">SLA {{ $application->sla_due_at->locale('id')->diffForHumans() }}</span>
    @endif
@endif
