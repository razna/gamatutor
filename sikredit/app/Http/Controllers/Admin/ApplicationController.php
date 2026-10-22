<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus as S;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CreditProduct;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\CreditScoringService;
use App\Services\LoanCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    /** Status antrian kerja per peran. */
    public static function queueFor(User $user, Builder $q): Builder
    {
        return match ($user->role) {
            Role::CustomerService => $q->status(S::Submitted),
            Role::Analis => $q->status(S::Analysis)->where(fn ($w) => $w->where('analyst_id', $user->id)->orWhereNull('analyst_id')),
            Role::KepalaCabang, Role::Komite => $q->status(S::Approval)->where('required_approver_role', $user->role->value),
            Role::Legal => $q->status(S::Approved),
            Role::Teller => $q->status(S::Contract),
            Role::Collection => $q->status(S::Active)->where('days_past_due', '>', 0),
            default => $q->whereIn('status', array_map(fn ($s) => $s->value, [S::Submitted, S::Analysis, S::Approval, S::Approved, S::Contract])),
        };
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $request->input('scope', 'queue');
        $query = LoanApplication::query()->visibleTo($user)->with('customer.user', 'product', 'branch', 'analyst')
            ->where('status', '!=', S::Draft->value);

        if ($scope === 'queue' && ! $request->filled('status')) {
            self::queueFor($user, $query);
        }
        $query->filter($request->only('q', 'status', 'product', 'branch', 'from', 'to'));

        if ($request->input('sla') === 'breached') {
            $query->whereNotNull('sla_due_at')->where('sla_due_at', '<', now());
        }

        $applications = $query->orderByRaw('sla_due_at is null')->orderBy('sla_due_at')->latest()->paginate(15)->withQueryString();

        return view('admin.applications.index', [
            'applications' => $applications,
            'scope' => $scope,
            'products' => CreditProduct::query()->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, LoanApplication $application, LoanCalculator $calc, CreditScoringService $scoring)
    {
        Gate::authorize('view-application', $application);
        $application->load('customer.user', 'product.documentTypes', 'branch', 'documents.type', 'documents.verifier',
            'collaterals.appraiser', 'survey.surveyor', 'analysis.analyst', 'logs.user', 'cs', 'analyst', 'approver', 'legalOfficer', 'disburser');

        $customer = $application->customer;
        $simulation = $calc->summary($application->finalAmount(), $application->finalRate(), $application->finalTenor(), $application->interest_method,
            $application->product->provision_fee, $application->product->admin_fee);
        $netIncome = $customer->totalIncome();
        $dsr = $scoring->dsr($netIncome, $customer->existing_installments, $simulation['max_installment']);
        $ltv = $scoring->ltv($application->finalAmount(), $application->totalCollateralValue());

        $analysts = User::query()->where('role', Role::Analis->value)->where('is_active', true)
            ->where(fn ($q) => $q->where('branch_id', $application->branch_id)->orWhereNull('branch_id'))->orderBy('name')->get();

        // Riwayat kredit nasabah di bank ini
        $history = LoanApplication::query()->where('customer_id', $customer->id)->where('id', '!=', $application->id)
            ->with('product')->latest()->get();

        return view('admin.applications.show', [
            'app' => $application,
            'customer' => $customer,
            'simulation' => $simulation,
            'dsr' => $dsr,
            'ltv' => $ltv,
            'netIncome' => $netIncome,
            'analysts' => $analysts,
            'history' => $history,
            'weights' => $scoring->weights(),
            'user' => $request->user(),
        ]);
    }
}
