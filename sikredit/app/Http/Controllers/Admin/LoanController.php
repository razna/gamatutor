<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus as S;
use App\Http\Controllers\Controller;
use App\Models\CollectionNote;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Models\Restructure;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function __construct(private LoanService $loans) {}

    public function index(Request $request)
    {
        $loans = LoanApplication::query()->visibleTo($request->user())
            ->status(...($request->input('status') === 'paid_off' ? [S::PaidOff] : [S::Active]))
            ->with('customer.user', 'product', 'branch')
            ->filter($request->only('q', 'product', 'branch'))
            ->when($request->filled('kol'), fn ($q) => $q->where('collectibility', $request->integer('kol')))
            ->latest('disbursed_at')->paginate(15)->withQueryString();

        return view('admin.loans.index', compact('loans'));
    }

    public function show(Request $request, LoanApplication $loan)
    {
        Gate::authorize('view-application', $loan);
        abort_unless(in_array($loan->status, [S::Active, S::PaidOff], true), 404);

        $quote = null;
        if ($loan->status === S::Active) {
            $this->loans->refresh($loan);
            $quote = $this->loans->payoffQuote($loan, $request->date('payoff_date') ?? today());
        }
        $loan->load('customer.user', 'product', 'branch', 'installments', 'payments.receiver', 'collectionNotes.user', 'restructures.user');

        return view('admin.loans.show', compact('loan', 'quote'));
    }

    public function pay(Request $request, LoanApplication $loan)
    {
        Gate::authorize('view-application', $loan);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $payment = $this->loans->recordPayment($loan, (float) $data['amount'], $request->user(), $data);

        return back()->with('success', "Pembayaran {$payment->receipt_number} sebesar ".rupiah($payment->amount).' berhasil dicatat.')
            ->with('receipt', $payment->id);
    }

    public function payoff(Request $request, LoanApplication $loan)
    {
        Gate::authorize('view-application', $loan);
        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Centang konfirmasi pelunasan.']);
        $payment = $this->loans->payoff($loan, $request->user(), $data);

        return back()->with('success', 'Pelunasan dipercepat berhasil. Kredit dinyatakan LUNAS.')->with('receipt', $payment->id);
    }

    public function restructure(Request $request, LoanApplication $loan)
    {
        Gate::authorize('view-application', $loan);
        $data = $request->validate([
            'scheme' => ['required', Rule::in(array_keys(Restructure::SCHEMES))],
            'new_tenor' => ['required', 'integer', 'min:1', 'max:360'],
            'new_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $this->loans->restructure($loan, $request->user(), $data);

        return back()->with('success', 'Restrukturisasi berhasil. Jadwal angsuran baru telah dibuat.');
    }

    public function note(Request $request, LoanApplication $loan)
    {
        Gate::authorize('view-application', $loan);
        $data = $request->validate([
            'action' => ['required', Rule::in(array_keys(CollectionNote::ACTIONS))],
            'result' => ['required', 'string', 'max:2000'],
            'promise_date' => ['nullable', 'date', 'after_or_equal:today'],
            'promise_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $loan->collectionNotes()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Catatan penagihan disimpan.');
    }

    /** Daftar kredit menunggak untuk tim collection. */
    public function collection(Request $request)
    {
        $loans = LoanApplication::query()->visibleTo($request->user())->status(S::Active)
            ->where('days_past_due', '>', 0)
            ->with(['customer.user', 'product', 'branch', 'collectionNotes' => fn ($q) => $q->latest()->limit(1)])
            ->when($request->filled('kol'), fn ($q) => $q->where('collectibility', $request->integer('kol')))
            ->filter($request->only('q', 'branch'))
            ->orderByDesc('days_past_due')->paginate(20)->withQueryString();

        $summary = LoanApplication::query()->visibleTo($request->user())->status(S::Active)
            ->selectRaw('collectibility, count(*) as total, sum(outstanding_principal) as outstanding')
            ->groupBy('collectibility')->get()->keyBy('collectibility');

        $promises = CollectionNote::query()->whereNotNull('promise_date')->whereBetween('promise_date', [today(), today()->addDays(7)])
            ->whereHas('application', fn ($q) => $q->visibleTo($request->user())->status(S::Active))
            ->with('application.customer.user')->orderBy('promise_date')->get();

        return view('admin.loans.collection', compact('loans', 'summary', 'promises'));
    }
}
