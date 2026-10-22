@props(['application'])
@php
    use App\Enums\ApplicationStatus as S;
    $steps = S::timeline();
    $current = $application->status;
    $terminalBad = in_array($current, [S::Rejected, S::Cancelled], true);
    $reached = collect($application->logs)->pluck('to_status')->map(fn ($s) => $s->value)->all();
    $currentIndex = array_search($current === S::Revision ? S::Submitted : $current, $steps, true);
@endphp
<div class="overflow-x-auto">
    <ol class="flex min-w-[640px] items-center">
        @foreach ($steps as $i => $step)
            @php
                $done = $currentIndex !== false ? $i < $currentIndex || ($i === $currentIndex && $current === S::PaidOff) : in_array($step->value, $reached, true);
                $active = $currentIndex === $i && ! $done;
            @endphp
            <li class="flex flex-1 items-center {{ $loop->last ? 'flex-none' : '' }}">
                <div class="flex flex-col items-center text-center">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold
                        {{ $done ? 'border-emerald-500 bg-emerald-500 text-white' : ($active ? 'border-brand-600 bg-brand-600 text-white ring-4 ring-brand-100' : 'border-slate-300 bg-white text-slate-400') }}">
                        @if ($done) ✓ @else {{ $i + 1 }} @endif
                    </div>
                    <div class="mt-1 w-24 text-[11px] font-medium {{ $active ? 'text-brand-700' : 'text-slate-500' }}">{{ $step->label() }}</div>
                </div>
                @unless ($loop->last)
                    <div class="mx-1 mb-5 h-0.5 flex-1 {{ $done ? 'bg-emerald-400' : 'bg-slate-200' }}"></div>
                @endunless
            </li>
        @endforeach
    </ol>
    @if ($terminalBad || $current === S::Revision)
        <div class="mt-3 rounded-lg px-4 py-2 text-sm {{ $current === S::Revision ? 'bg-amber-50 text-amber-800' : 'bg-rose-50 text-rose-800' }}">
            Status saat ini: <strong>{{ $current->label() }}</strong>
        </div>
    @endif
</div>
