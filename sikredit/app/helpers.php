<?php

use Carbon\Carbon;
use Carbon\CarbonInterface;

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $value, bool $withPrefix = true): string
    {
        $formatted = number_format((float) $value, 0, ',', '.');

        return $withPrefix ? 'Rp '.$formatted : $formatted;
    }
}

if (! function_exists('rupiah_singkat')) {
    /** Rp 975,3 jt / Rp 1,2 M — untuk kartu statistik. */
    function rupiah_singkat(float|int|string|null $value): string
    {
        $v = (float) $value;
        foreach ([1e12 => 'T', 1e9 => 'M', 1e6 => 'jt'] as $div => $suffix) {
            if (abs($v) >= $div) {
                return 'Rp '.number_format($v / $div, 1, ',', '.').' '.$suffix;
            }
        }

        return rupiah($v);
    }
}

if (! function_exists('tanggal')) {
    /** Format tanggal Indonesia, mis. 21 Oktober 2026. */
    function tanggal(CarbonInterface|string|null $date, bool $withTime = false): string
    {
        if (! $date) {
            return '-';
        }
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return $date->locale('id')->translatedFormat($withTime ? 'd F Y, H:i' : 'd F Y');
    }
}

if (! function_exists('terbilang')) {
    function terbilang(float|int $n): string
    {
        $n = (int) floor(abs($n));
        $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $f = function (int $x) use (&$f, $huruf): string {
            return match (true) {
                $x < 12 => $huruf[$x],
                $x < 20 => $f($x - 10).' belas',
                $x < 100 => $f(intdiv($x, 10)).' puluh '.$f($x % 10),
                $x < 200 => 'seratus '.$f($x - 100),
                $x < 1000 => $f(intdiv($x, 100)).' ratus '.$f($x % 100),
                $x < 2000 => 'seribu '.$f($x - 1000),
                $x < 1000000 => $f(intdiv($x, 1000)).' ribu '.$f($x % 1000),
                $x < 1000000000 => $f(intdiv($x, 1000000)).' juta '.$f($x % 1000000),
                $x < 1000000000000 => $f(intdiv($x, 1000000000)).' miliar '.$f($x % 1000000000),
                default => $f(intdiv($x, 1000000000000)).' triliun '.$f($x % 1000000000000),
            };
        };

        return $n === 0 ? 'nol rupiah' : trim(preg_replace('/\s+/', ' ', $f($n))).' rupiah';
    }
}
