<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ApplicationStatus as S;
use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Services\LoanService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = $request->user()->customer->applications()
            ->status(S::Active, S::PaidOff)
            ->with('product', 'activeInstallments')
            ->latest('disbursed_at')->get();

        return view('customer.loans.index', compact('loans'));
    }

    public function show(Request $request, LoanApplication $loan, LoanService $service)
    {
        abort_unless($loan->customer->user_id === $request->user()->id, 403);
        abort_unless(in_array($loan->status, [S::Active, S::PaidOff], true), 404);

        $quote = null;
        if ($loan->status === S::Active) {
            $service->refresh($loan);
            $quote = $service->payoffQuote($loan, $request->date('payoff_date') ?? today());
        }
        $loan->load('product', 'activeInstallments', 'payments', 'restructures');

        return view('customer.loans.show', compact('loan', 'quote'));
    }
}
