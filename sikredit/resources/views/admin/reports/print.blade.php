<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 16px; margin: 0; } .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; } th { background: #eee; }
        .totals td { border: none; padding: 2px 8px 2px 0; }
        @media print { .no-print { display: none; } @page { size: A4 landscape; margin: 12mm; } }
    </style>
</head>
<body onload="window.print()">
    <button class="no-print" onclick="window.print()">Cetak</button>
    <h1>SiKredit Bank — {{ $title }}</h1>
    <div class="muted">Periode {{ tanggal($from) }} s.d. {{ tanggal($to) }} · Dicetak {{ tanggal(now(), true) }} oleh {{ auth()->user()->name }}</div>
    <table class="totals">@foreach ($totals as $k => $v)<tr><td>{{ $k }}</td><td><strong>{{ $v }}</strong></td></tr>@endforeach</table>
    <table>
        <thead><tr><th>#</th>@foreach ($columns as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>@foreach ($rows as $i => $row)<tr><td>{{ $i + 1 }}</td>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody>
    </table>
</body>
</html>
