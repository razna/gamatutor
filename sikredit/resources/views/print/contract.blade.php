@extends('print.layout')
@section('title', 'Perjanjian Kredit '.$app->contract_number)
@section('content')
    @php $c = $app->customer; $date = $app->akad_date ?? $app->akad_scheduled_at ?? now(); @endphp
    <h1>Perjanjian Kredit</h1>
    <div class="center muted">Nomor: {{ $app->contract_number }}</div>
    <p style="margin-top:12px">Pada hari ini, {{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y') }}, yang bertanda tangan di bawah ini:</p>
    <ol>
        <li><strong>SiKredit Bank</strong>, {{ $app->branch->name }}, dalam hal ini diwakili oleh {{ $app->approver?->name }} selaku {{ $app->approver?->role->label() }}, selanjutnya disebut <strong>BANK</strong>.</li>
        <li><strong>{{ $c->user->name }}</strong>, NIK {{ $c->nik }}, beralamat di {{ $c->address }}, {{ $c->city }}, selanjutnya disebut <strong>DEBITUR</strong>.</li>
    </ol>
    <p>Para pihak sepakat mengadakan perjanjian kredit dengan ketentuan sebagai berikut:</p>
    <p><strong>Pasal 1 — Fasilitas Kredit.</strong> BANK memberikan fasilitas {{ $app->product->name }} kepada DEBITUR sebesar {{ rupiah($app->approved_amount) }} ({{ terbilang($app->approved_amount) }}).</p>
    <p><strong>Pasal 2 — Jangka Waktu.</strong> Kredit berlaku selama {{ $app->approved_tenor }} bulan terhitung sejak tanggal pencairan.</p>
    <p><strong>Pasal 3 — Bunga dan Angsuran.</strong> Suku bunga {{ $app->finalRate() }}% per tahun dengan metode {{ $app->interest_method->label() }}. DEBITUR wajib membayar angsuran setiap bulan sesuai jadwal angsuran yang menjadi bagian tidak terpisahkan dari perjanjian ini.</p>
    <p><strong>Pasal 4 — Biaya.</strong> Provisi {{ $app->product->provision_fee }}% dan biaya administrasi {{ rupiah($app->product->admin_fee) }} dipotong pada saat pencairan.</p>
    <p><strong>Pasal 5 — Denda.</strong> Atas keterlambatan pembayaran dikenakan denda {{ $app->product->penalty_rate }}% per hari dari jumlah angsuran tertunggak.</p>
    <p><strong>Pasal 6 — Pelunasan Dipercepat.</strong> DEBITUR dapat melunasi kredit sebelum jatuh tempo dengan biaya pelunasan {{ \App\Models\Setting::get('early_payoff_penalty') }}% dari sisa pokok.</p>
    @if ($app->collaterals->isNotEmpty())
        <p><strong>Pasal 7 — Agunan.</strong> Untuk menjamin pelunasan kredit, DEBITUR menyerahkan agunan berikut yang diikat sesuai ketentuan hukum yang berlaku:</p>
        <table class="grid"><tr><th>Jenis</th><th>Uraian</th><th>No. Bukti Kepemilikan</th><th>Pengikatan</th></tr>
            @foreach ($app->collaterals as $col)<tr><td>{{ $col->typeLabel() }}</td><td>{{ $col->description }}</td><td>{{ $col->ownership_number }}</td><td>{{ $col->binding_type ? \App\Models\Collateral::BINDINGS[$col->binding_type] : '-' }} {{ $col->binding_number }}</td></tr>@endforeach
        </table>
    @endif
    <p><strong>Pasal 8 — Wanprestasi.</strong> Apabila DEBITUR lalai memenuhi kewajiban, BANK berhak menagih seluruh kewajiban seketika dan sekaligus serta melakukan eksekusi agunan sesuai peraturan perundang-undangan.</p>
    <p><strong>Pasal 9 — Pelindungan Data.</strong> DEBITUR menyetujui pemrosesan data pribadi oleh BANK untuk keperluan administrasi dan pelaporan kredit (termasuk SLIK OJK) sesuai UU Pelindungan Data Pribadi.</p>
    @if ($app->approval_conditions)<p><strong>Pasal 10 — Syarat Khusus.</strong> {{ $app->approval_conditions }}</p>@endif
    @if ($app->notary_name)<p class="muted">Pengikatan agunan dilakukan di hadapan Notaris/PPAT {{ $app->notary_name }}.</p>@endif
    <table class="sign"><tr>
        <td>DEBITUR<br><br><br><br><br><strong>{{ $c->user->name }}</strong></td>
        <td>BANK<br><br><br><br><br><strong>{{ $app->approver?->name }}</strong></td>
    </tr></table>
@endsection
