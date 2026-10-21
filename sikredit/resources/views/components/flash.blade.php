@if (session('success'))
    <div x-data="{ show: true }" x-show="show" class="mb-4 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <svg class="mt-0.5 h-5 w-5 flex-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        <div class="flex-1">{{ session('success') }}
            @if (session('receipt')) <a href="{{ route('print.receipt', session('receipt')) }}" target="_blank" class="ml-1 font-semibold underline">Cetak kuitansi</a>@endif
        </div>
        <button @click="show = false" class="text-emerald-600">&times;</button>
    </div>
@endif
@if (session('warning'))
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <div class="font-semibold">Terdapat kesalahan:</div>
        <ul class="mt-1 list-inside list-disc">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
