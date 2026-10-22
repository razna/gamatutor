@extends('print.layout')
@section('title', 'Memo Analisis Kredit '.$app->number)
@section('content')
    @php $an = $app->analysis; $c = $app->customer; @endphp
    <h1>Memorandum Analisis Kredit</h1>
    <div class="center muted">Pengajuan {{ $app->number }} · {{ tanggal($an->updated_at) }}</div>
    <p><strong>I. Data Pemohon</strong></p>
    <table class="kv">
        <tr><td>Nama / Usia</td><td>: {{ $c->user->name }} / {{ $c->age() }} tahun</td></tr>
        <tr><td>Pekerjaan</td><td>: {{ \App\Models\Customer::OCCUPATIONS[$c->occupation_type] ?? '-' }} — {{ $c->employer_name }} ({{ $c->work_years }} th)</td></tr>
        <tr><td>Total penghasilan</td><td>: {{ rupiah($an->net_income) }} / bulan</td></tr>
        <tr><td>Cicilan berjalan</td><td>: {{ rupiah($c->existing_installments) }} / bulan</td></tr>
    </table>
    <p><strong>II. Permohonan & Rekomendasi</strong></p>
    <table class="grid">
        <tr><th></th><th>Permohonan</th><th>Rekomendasi Analis</th></tr>
        <tr><td>Plafon</td><td>{{ rupiah($app->amount) }}</td><td>{{ rupiah($an->recommended_amount) }}</td></tr>
        <tr><td>Tenor</td><td>{{ $app->tenor }} bulan</td><td>{{ $an->recommended_tenor }} bulan</td></tr>
        <tr><td>Suku bunga</td><td>{{ $app->interest_rate }}%</td><td>{{ $an->recommended_rate }}%</td></tr>
        <tr><td>Angsuran</td><td></td><td>{{ rupiah($an->new_installment) }}</td></tr>
    </table>
    <p><strong>III. Analisis 5C</strong></p>
    <table class="grid">
        <tr><th>Aspek</th><th>Skor</th><th>Catatan</th></tr>
        @foreach (\App\Models\CreditAnalysis::ASPECTS as $k => [$label])
            <tr><td>{{ $label }}</td><td class="center">{{ $an->{$k.'_score'} }}</td><td>{{ $an->{$k.'_notes'} }}</td></tr>
        @endforeach
    </table>
    <p><strong>IV. Rasio & Riwayat Kredit</strong></p>
    <table class="kv">
        <tr><td>Debt Service Ratio (DSR)</td><td>: {{ $an->dsr }}% (batas {{ \App\Models\Setting::get('max_dsr') }}%)</td></tr>
        <tr><td>Loan to Value (LTV)</td><td>: {{ $an->ltv !== null ? $an->ltv.'%' : '-' }} (batas {{ $app->product->max_ltv }}%)</td></tr>
        <tr><td>SLIK OJK</td><td>: Kolektibilitas {{ $an->slik_collectibility }} — {{ $an->slik_notes ?: '-' }}</td></tr>
        <tr><td>Hasil survei</td><td>: {{ $app->survey ? 'Dikunjungi '.tanggal($app->survey->visit_date).'. '.$app->survey->notes : '-' }}</td></tr>
    </table>
    <div class="box"><strong>Skor akhir: {{ $an->total_score }} — Grade {{ $an->grade }}</strong> ({{ $gradeText }})<br>
        Rekomendasi: <strong>{{ $an->recommendation === 'approve' ? 'LAYAK DISETUJUI' : 'TIDAK LAYAK' }}</strong><br>{{ $an->summary }}</div>
    <table class="sign"><tr><td>Analis Kredit<br><br><br><br><strong>{{ $an->analyst->name }}</strong></td><td>Pemutus Kredit<br><br><br><br><strong>{{ $app->approver?->name ?? '..............................' }}</strong></td></tr></table>
@endsection
