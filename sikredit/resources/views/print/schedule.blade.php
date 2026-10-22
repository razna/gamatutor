@extends('print.layout')
@section('title', 'Jadwal Angsuran '.$app->number)
@section('content')
    <h1>Jadwal Angsuran Kredit</h1>
    <table class="kv" style="margin:10px 0">
        <tr><td>Debitur</td><td>: {{ $app->customer->user->name }}</td><td>No. Perjanjian</td><td>: {{ $app->contract_number }}</td></tr>
        <tr><td>Fasilitas</td><td>: {{ $app->product->name }}</td><td>Tanggal cair</td><td>: {{ tanggal($app->disbursed_at) }}</td></tr>
        <tr><td>Plafon</td><td>: {{ rupiah($app->approved_amount) }}</td><td>Bunga</td><td>: {{ $app->finalRate() }}% p.a. ({{ $app->interest_method->label() }})</td></tr>
    </table>
    <table class="grid">
        <thead><tr><th>Ke</th><th>Jatuh Tempo</th><th class="right">Pokok</th><th class="right">Bunga</th><th class="right">Angsuran</th><th class="right">Sisa Pokok</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($app->activeInstallments as $i)
            <tr><td class="center">{{ $i->number }}</td><td>{{ $i->due_date->format('d/m/Y') }}</td><td class="right">{{ rupiah($i->principal, false) }}</td><td class="right">{{ rupiah($i->interest, false) }}</td><td class="right">{{ rupiah($i->amount, false) }}</td><td class="right">{{ rupiah($i->balance_after, false) }}</td><td>{{ $i->statusBadge()[0] }}</td></tr>
        @endforeach
        <tr><th colspan="2">Total</th><th class="right">{{ rupiah($app->activeInstallments->sum('principal'), false) }}</th><th class="right">{{ rupiah($app->activeInstallments->sum('interest'), false) }}</th><th class="right">{{ rupiah($app->activeInstallments->sum('amount'), false) }}</th><th colspan="2"></th></tr>
        </tbody>
    </table>
    <p class="muted">Dicetak {{ tanggal(now(), true) }}. Tanggal jatuh tempo yang jatuh pada hari libur digeser ke hari kerja berikutnya.</p>
@endsection
