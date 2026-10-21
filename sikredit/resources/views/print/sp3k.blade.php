@extends('print.layout')
@section('title', 'SP3K '.$app->sp3k_number)
@section('content')
    <h1>Surat Persetujuan Prinsip Pemberian Kredit (SP3K)</h1>
    <div class="center muted">Nomor: {{ $app->sp3k_number }}</div>
    <p style="margin-top:14px">Kepada Yth.<br><strong>{{ $app->customer->user->name }}</strong><br>{{ $app->customer->address }}, {{ $app->customer->city }}</p>
    <p>Dengan hormat, menindaklanjuti permohonan kredit Saudara nomor <strong>{{ $app->number }}</strong> tanggal {{ tanggal($app->submitted_at) }}, dengan ini kami sampaikan bahwa permohonan tersebut <strong>DISETUJUI</strong> dengan ketentuan sebagai berikut:</p>
    <table class="kv box">
        <tr><td>Jenis fasilitas</td><td>: {{ $app->product->name }}</td></tr>
        <tr><td>Plafon kredit</td><td>: {{ rupiah($app->approved_amount) }} ({{ terbilang($app->approved_amount) }})</td></tr>
        <tr><td>Jangka waktu</td><td>: {{ $app->approved_tenor }} bulan</td></tr>
        <tr><td>Suku bunga</td><td>: {{ $app->approved_rate }}% per tahun, {{ $app->interest_method->label() }}</td></tr>
        <tr><td>Estimasi angsuran</td><td>: {{ rupiah(app(\App\Services\LoanCalculator::class)->summary($app->approved_amount, $app->approved_rate, $app->approved_tenor, $app->interest_method)['first_installment']) }} per bulan</td></tr>
        <tr><td>Provisi</td><td>: {{ $app->product->provision_fee }}% ({{ rupiah($app->approved_amount * $app->product->provision_fee / 100) }})</td></tr>
        <tr><td>Biaya administrasi</td><td>: {{ rupiah($app->product->admin_fee) }}</td></tr>
        <tr><td>Denda keterlambatan</td><td>: {{ $app->product->penalty_rate }}% per hari dari angsuran tertunggak</td></tr>
        <tr><td>Tujuan penggunaan</td><td>: {{ \App\Models\LoanApplication::PURPOSES[$app->purpose] ?? $app->purpose }}</td></tr>
    </table>
    @if ($app->collaterals->isNotEmpty())
        <p><strong>Agunan:</strong></p>
        <ol>@foreach ($app->collaterals as $c)<li>{{ $c->typeLabel() }} — {{ $c->description }} (No. {{ $c->ownership_number ?: '-' }} a.n. {{ $c->owner_name ?: '-' }})</li>@endforeach</ol>
    @endif
    @if ($app->approval_conditions)<p><strong>Syarat tambahan:</strong> {{ $app->approval_conditions }}</p>@endif
    <p>SP3K ini berlaku 30 (tiga puluh) hari sejak tanggal diterbitkan. Penandatanganan perjanjian kredit akan dijadwalkan oleh petugas kami.</p>
    <table class="sign"><tr>
        <td>Menyetujui,<br>Debitur<br><br><br><br><strong>{{ $app->customer->user->name }}</strong></td>
        <td>{{ $app->branch->city }}, {{ tanggal($app->decided_at) }}<br>SiKredit Bank<br><br><br><br><strong>{{ $app->approver?->name }}</strong><br>{{ $app->approver?->role->label() }}</td>
    </tr></table>
@endsection
