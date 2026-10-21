@extends('print.layout')
@section('title', 'Kuitansi '.$payment->receipt_number)
@section('content')
    <h1>Bukti Pembayaran</h1>
    <div class="center muted">No. {{ $payment->receipt_number }}</div>
    <table class="kv box" style="margin-top:12px">
        <tr><td>Tanggal bayar</td><td>: {{ tanggal($payment->paid_at) }}</td></tr>
        <tr><td>Nama debitur</td><td>: {{ $app->customer->user->name }}</td></tr>
        <tr><td>No. kredit / perjanjian</td><td>: {{ $app->number }} / {{ $app->contract_number }}</td></tr>
        <tr><td>Jenis pembayaran</td><td>: {{ $payment->type === 'payoff' ? 'Pelunasan dipercepat' : 'Angsuran' }}</td></tr>
        <tr><td>Metode</td><td>: {{ \App\Models\Payment::METHODS[$payment->method] }} {{ $payment->reference ? '(Ref. '.$payment->reference.')' : '' }}</td></tr>
        <tr><td>Pembayaran pokok</td><td>: {{ rupiah($payment->principal_paid) }}</td></tr>
        <tr><td>Pembayaran bunga{{ $payment->type === 'payoff' ? ' & biaya' : '' }}</td><td>: {{ rupiah($payment->interest_paid) }}</td></tr>
        <tr><td>Pembayaran denda</td><td>: {{ rupiah($payment->penalty_paid) }}</td></tr>
        <tr><td><strong>Total dibayar</strong></td><td>: <strong>{{ rupiah($payment->amount) }}</strong></td></tr>
        <tr><td>Terbilang</td><td>: <em>{{ ucfirst(terbilang($payment->amount)) }}</em></td></tr>
        <tr><td>Sisa pokok saat ini</td><td>: {{ rupiah($app->outstanding_principal) }}</td></tr>
    </table>
    <table class="sign"><tr><td></td><td>{{ $app->branch->city }}, {{ tanggal($payment->paid_at) }}<br>Petugas<br><br><br><br><strong>{{ $payment->receiver?->name ?? 'Sistem' }}</strong></td></tr></table>
    <p class="muted">Bukti pembayaran ini sah tanpa tanda tangan basah apabila dicetak dari sistem.</p>
@endsection
