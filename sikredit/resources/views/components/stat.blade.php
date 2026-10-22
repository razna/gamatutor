@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'tone' => 'brand'])
@php
$tones = ['brand' => 'bg-brand-50 text-brand-700', 'green' => 'bg-emerald-50 text-emerald-700', 'amber' => 'bg-amber-50 text-amber-700', 'rose' => 'bg-rose-50 text-rose-700', 'violet' => 'bg-violet-50 text-violet-700'];
$len = mb_strlen((string) $value);
@endphp
<div class="card p-5">
    <div class="flex items-center justify-between gap-2">
        <div class="truncate text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</div>
        <div class="flex-none rounded-lg p-1.5 {{ $tones[$tone] ?? $tones['brand'] }}"><x-icon :name="$icon" class="h-4 w-4" /></div>
    </div>
    <div class="mt-2 truncate font-bold leading-tight text-slate-900 {{ $len > 16 ? 'text-lg' : ($len > 12 ? 'text-xl' : 'text-2xl') }}" title="{{ $value }}">{{ $value }}</div>
    @if ($hint)<div class="mt-1 text-xs text-slate-500">{{ $hint }}</div>@endif
</div>
