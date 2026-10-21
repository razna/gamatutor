<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus as S;
use App\Models\LoanApplication;
use App\Services\LoanService;
use Illuminate\Console\Command;

class RefreshLoans extends Command
{
    protected $signature = 'kredit:refresh';

    protected $description = 'Hitung ulang denda keterlambatan, hari tunggakan, kolektibilitas, dan outstanding seluruh kredit aktif';

    public function handle(LoanService $service): int
    {
        $count = 0;
        LoanApplication::query()->status(S::Active)->with('product')->chunkById(100, function ($loans) use ($service, &$count) {
            foreach ($loans as $loan) {
                $service->refresh($loan);
                $count++;
            }
        });
        $this->info("{$count} kredit aktif diperbarui.");

        return self::SUCCESS;
    }
}
