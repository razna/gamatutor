<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/** Kalender hari kerja (Senin–Jumat, di luar hari libur) untuk SLA dan jatuh tempo. */
class BusinessCalendar
{
    private ?array $holidays = null;

    public function isWorkingDay(CarbonInterface $date): bool
    {
        return ! $date->isWeekend() && ! in_array($date->toDateString(), $this->holidays(), true);
    }

    /** Tambah jam kerja: tiap 24 jam dihitung satu hari kerja. */
    public function addWorkingHours(CarbonInterface $start, int $hours): Carbon
    {
        $date = Carbon::instance($start);
        $days = intdiv($hours, 24);
        $rest = $hours % 24;

        while ($days > 0) {
            $date->addDay();
            if ($this->isWorkingDay($date)) {
                $days--;
            }
        }
        $date->addHours($rest);
        while (! $this->isWorkingDay($date)) {
            $date->addDay();
        }

        return $date;
    }

    public function nextWorkingDay(CarbonInterface $date): Carbon
    {
        $d = Carbon::instance($date);
        while (! $this->isWorkingDay($d)) {
            $d->addDay();
        }

        return $d;
    }

    private function holidays(): array
    {
        return $this->holidays ??= Holiday::query()->pluck('date')->map(fn ($d) => $d->toDateString())->all();
    }
}
