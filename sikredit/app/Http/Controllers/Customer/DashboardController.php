<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ApplicationStatus as S;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $customer = $request->user()->customer;
        $applications = $customer->applications()->with('product')->latest()->get();
        $activeLoans = $applications->where('status', S::Active);
        $activeLoans->load('activeInstallments');

        $nextDue = $activeLoans->map(fn ($a) => $a->nextInstallment())->filter()->sortBy('due_date')->first();

        return view('customer.dashboard', [
            'customer' => $customer,
            'applications' => $applications,
            'inProcess' => $applications->filter(fn ($a) => $a->status->isInProcess()),
            'activeLoans' => $activeLoans,
            'nextDue' => $nextDue,
            'outstanding' => $activeLoans->sum('outstanding_principal'),
        ]);
    }
}
