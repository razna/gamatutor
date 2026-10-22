<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus as S;
use App\Http\Controllers\Controller;
use App\Models\ApplicationLog;
use App\Models\LoanApplication;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $base = fn () => LoanApplication::query()->visibleTo($user);
        $year = now()->year;

        $submitted = $base()->whereNotNull('submitted_at');
        $decided = $base()->whereNotNull('decided_at');
        $approvedCount = (clone $decided)->whereNotNull('approver_id')->whereNotIn('status', [S::Rejected->value])->count();
        $decidedCount = (clone $decided)->count();

        $active = $base()->status(S::Active);
        $outstanding = (float) (clone $active)->sum('outstanding_principal');
        $npl = (float) (clone $active)->where('collectibility', '>=', 3)->sum('outstanding_principal');

        // Rata-rata waktu proses (hari) dari pengajuan hingga keputusan
        $avgDays = $base()->whereNotNull('decided_at')->whereNotNull('submitted_at')->get(['submitted_at', 'decided_at'])
            ->avg(fn ($a) => $a->submitted_at->diffInHours($a->decided_at) / 24);

        $stats = [
            'submitted_month' => (clone $submitted)->where('submitted_at', '>=', now()->startOfMonth())->count(),
            'in_process' => $base()->status(S::Submitted, S::Analysis, S::Approval, S::Approved, S::Contract)->count(),
            'approval_rate' => $decidedCount ? round($approvedCount / $decidedCount * 100, 1) : 0,
            'active_loans' => (clone $active)->count(),
            'outstanding' => $outstanding,
            'npl_ratio' => $outstanding > 0 ? round($npl / $outstanding * 100, 2) : 0,
            'sla_breached' => $base()->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->count(),
            'avg_days' => round((float) $avgDays, 1),
            'my_queue' => ApplicationController::queueFor($user, $base()->where('status', '!=', 'draft'))->count(),
            'collections_today' => (float) Payment::query()->whereDate('paid_at', today())
                ->whereHas('application', fn ($q) => $q->visibleTo($user))->sum('amount'),
        ];

        // Tren bulanan tahun berjalan
        $months = collect(range(1, 12));
        $monthExpr = match (DB::connection()->getDriverName()) {
            'sqlite' => "cast(strftime('%m', :col) as integer)",
            'pgsql' => 'extract(month from :col)',
            default => 'month(:col)',
        };
        $countBy = fn ($col, $q) => $q->whereYear($col, $year)
            ->selectRaw(str_replace(':col', $col, $monthExpr).' as m, count(*) as c, sum(coalesce(approved_amount, amount)) as s')
            ->groupBy('m')->get()->keyBy('m');
        $sub = $countBy('submitted_at', $base());
        $dis = $countBy('disbursed_at', $base()->whereNotNull('disbursed_at'));

        $chart = [
            'labels' => $months->map(fn ($m) => now()->setMonth($m)->locale('id')->translatedFormat('M'))->all(),
            'submitted' => $months->map(fn ($m) => (int) ($sub[$m]->c ?? 0))->all(),
            'disbursed' => $months->map(fn ($m) => round(($dis[$m]->s ?? 0) / 1e6, 1))->all(),
        ];

        $byStatus = $base()->where('status', '!=', 'draft')->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $byProduct = $base()->status(S::Active, S::PaidOff)->join('credit_products', 'credit_products.id', '=', 'loan_applications.credit_product_id')
            ->selectRaw('credit_products.category as cat, sum(coalesce(approved_amount, amount)) as s')->groupBy('cat')->pluck('s', 'cat');
        $byKol = (clone $active)->selectRaw('collectibility, count(*) as c')->groupBy('collectibility')->pluck('c', 'collectibility');

        $queue = ApplicationController::queueFor($user, $base()->where('status', '!=', 'draft'))
            ->with('customer.user', 'product')->orderByRaw('sla_due_at is null')->orderBy('sla_due_at')->limit(8)->get();

        $recent = ApplicationLog::query()->whereHas('application', fn ($q) => $q->visibleTo($user))
            ->with('application.customer.user', 'user')->latest('id')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'chart', 'byStatus', 'byProduct', 'byKol', 'queue', 'recent', 'user'));
    }
}
