<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; color: #111; line-height: 1.5; }
        .kop { border-bottom: 3px double #1549de; padding-bottom: 8px; margin-bottom: 14px; }
        .kop .bank { font-size: 18px; font-weight: bold; color: #1549de; }
        .kop .addr { font-size: 9px; color: #555; }
        h1 { font-size: 14px; text-align: center; margin: 6px 0 2px; text-transform: uppercase; }
        .center { text-align: center; } .right { text-align: right; } .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td { border: 1px solid #888; padding: 4px 6px; }
        table.grid th { background: #eef2ff; }
        table.kv td { padding: 2px 4px; vertical-align: top; }
        table.kv td:first-child { width: 36%; }
        .sign { margin-top: 30px; } .sign td { text-align: center; width: 50%; vertical-align: top; }
        .box { border: 1px solid #888; padding: 8px; margin: 8px 0; }
        ol, ul { margin: 4px 0; padding-left: 18px; }
        .no-print { margin-bottom: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body @isset($autoPrint) onload="window.print()" @endisset>
    @isset($autoPrint)<div class="no-print"><button onclick="window.print()">Cetak</button></div>@endisset
    <div class="kop">
        <table><tr>
            <td><div class="bank">SiKredit Bank</div><div class="addr">{{ $app->branch->name ?? 'Kantor Pusat' }} · {{ $app->branch->address ?? '' }} {{ $app->branch->city ?? '' }} · Telp. {{ $app->branch->phone ?? '1500-123' }}</div></td>
            <td class="right muted" style="font-size:9px">Berizin & diawasi OJK<br>Peserta penjaminan LPS</td>
        </tr></table>
    </div>
    @yield('content')
</body>
</html>
