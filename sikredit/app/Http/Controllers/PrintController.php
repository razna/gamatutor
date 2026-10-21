<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus as S;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Services\CreditScoringService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Cetak dokumen resmi (PDF via DomPDF, atau HTML siap cetak bila DomPDF tidak terpasang). */
class PrintController extends Controller
{
    private function render(string $view, array $data, string $filename)
    {
        if (class_exists(Pdf::class) && ! request()->boolean('html')) {
            return Pdf::loadView($view, $data)->setPaper('a4')->stream($filename.'.pdf');
        }

        return view($view, $data + ['autoPrint' => true]);
    }

    private function load(LoanApplication $app): LoanApplication
    {
        Gate::authorize('view-application', $app);

        return $app->load('customer.user', 'product', 'branch', 'collaterals', 'approver', 'analyst', 'analysis', 'survey');
    }

    public function sp3k(LoanApplication $application)
    {
        abort_unless(in_array($application->status, [S::Approved, S::Contract, S::Active, S::PaidOff], true), 404);

        return $this->render('print.sp3k', ['app' => $this->load($application)], 'SP3K-'.$application->id);
    }

    public function contract(LoanApplication $application)
    {
        abort_unless($application->contract_number, 404, 'Nomor perjanjian belum diterbitkan.');

        return $this->render('print.contract', ['app' => $this->load($application)], 'Perjanjian-Kredit-'.$application->id);
    }

    public function schedule(LoanApplication $application)
    {
        abort_unless(in_array($application->status, [S::Active, S::PaidOff], true), 404);
        $this->load($application)->load('activeInstallments');

        return $this->render('print.schedule', ['app' => $application], 'Jadwal-Angsuran-'.$application->id);
    }

    public function receipt(Payment $payment)
    {
        $app = $this->load($payment->application);

        return $this->render('print.receipt', ['app' => $app, 'payment' => $payment->load('receiver')], 'Kuitansi-'.$payment->id);
    }

    public function memo(Request $request, LoanApplication $application)
    {
        abort_unless($request->user()->isStaff(), 403);
        abort_unless($application->analysis, 404, 'Analisis kredit belum dibuat.');

        return $this->render('print.memo', [
            'app' => $this->load($application),
            'gradeText' => CreditScoringService::gradeDescription($application->analysis->grade),
        ], 'Memo-Analisis-'.$application->id);
    }
}
