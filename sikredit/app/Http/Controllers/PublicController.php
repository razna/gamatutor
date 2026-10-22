<?php

namespace App\Http\Controllers;

use App\Enums\InterestMethod;
use App\Models\Branch;
use App\Models\CreditProduct;
use App\Models\Setting;
use App\Services\LoanCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicController extends Controller
{
    public function home()
    {
        $products = CreditProduct::query()->active()->orderBy('id')->get();

        return view('public.home', compact('products'));
    }

    public function products()
    {
        $products = CreditProduct::query()->active()->with('documentTypes')->orderBy('category')->get();

        return view('public.products', compact('products'));
    }

    public function product(CreditProduct $product)
    {
        abort_unless($product->is_active, 404);
        $product->load('documentTypes');

        return view('public.product', compact('product'));
    }

    public function simulation(Request $request, LoanCalculator $calc)
    {
        $products = CreditProduct::query()->active()->get();
        $data = $request->validate([
            'product' => ['nullable', 'exists:credit_products,id'],
            'amount' => ['nullable', 'numeric', 'min:1000000', 'max:100000000000'],
            'tenor' => ['nullable', 'integer', 'min:1', 'max:360'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'method' => ['nullable', Rule::enum(InterestMethod::class)],
        ]);

        $product = isset($data['product']) ? $products->firstWhere('id', $data['product']) : null;
        $result = null;
        if (isset($data['amount'], $data['tenor'])) {
            $rate = $data['rate'] ?? $product?->interest_rate ?? 10;
            $method = $data['method'] ?? $product?->interest_method?->value ?? 'anuitas';
            $result = $calc->summary((float) $data['amount'], (float) $rate, (int) $data['tenor'], $method, $product?->provision_fee ?? 0, $product?->admin_fee ?? 0);
            $result += ['rate' => $rate, 'method' => InterestMethod::from($method), 'amount' => (float) $data['amount'], 'tenor' => (int) $data['tenor']];
        }

        return view('public.simulation', compact('products', 'product', 'result'));
    }

    /** Cek kelayakan awal (pre-screening) tanpa login. */
    public function eligibility(Request $request, LoanCalculator $calc)
    {
        $products = CreditProduct::query()->active()->get();
        $result = null;

        if ($request->isMethod('post')) {
            $d = $request->validate([
                'product' => ['required', 'exists:credit_products,id'],
                'age' => ['required', 'integer', 'min:17', 'max:80'],
                'income' => ['required', 'numeric', 'min:0'],
                'installments' => ['required', 'numeric', 'min:0'],
                'amount' => ['required', 'numeric', 'min:1000000'],
                'tenor' => ['required', 'integer', 'min:1', 'max:360'],
                'work_years' => ['required', 'integer', 'min:0', 'max:50'],
                'has_bad_credit' => ['required', 'boolean'],
            ]);
            $p = $products->firstWhere('id', $d['product']);
            $sum = $calc->summary((float) $d['amount'], $p->interest_rate, (int) $d['tenor'], $p->interest_method);
            $installment = $sum['max_installment'];
            $dsr = $d['income'] > 0 ? ($d['installments'] + $installment) / $d['income'] * 100 : 100;
            $maxDsr = (float) Setting::get('max_dsr');
            $ageAtEnd = $d['age'] + $d['tenor'] / 12;

            $checks = [
                ['Usia 21–65 tahun saat pengajuan', $d['age'] >= 21 && $d['age'] <= 65],
                ['Usia saat kredit lunas maksimal 70 tahun ('.number_format($ageAtEnd, 1, ',', '.').' th)', $ageAtEnd <= 70],
                ['Plafon sesuai ketentuan produk ('.rupiah($p->min_amount).' – '.rupiah($p->max_amount).')', $d['amount'] >= $p->min_amount && $d['amount'] <= $p->max_amount],
                ["Tenor sesuai ketentuan produk ({$p->min_tenor}–{$p->max_tenor} bulan)", $d['tenor'] >= $p->min_tenor && $d['tenor'] <= $p->max_tenor],
                ['Rasio cicilan (DSR) maksimal '.$maxDsr.'% — Anda: '.number_format($dsr, 1, ',', '.').'%', $dsr <= $maxDsr],
                ['Masa kerja/usaha minimal 1 tahun', $d['work_years'] >= 1],
                ['Tidak memiliki riwayat kredit bermasalah', ! $d['has_bad_credit']],
            ];
            $passed = count(array_filter($checks, fn ($c) => $c[1]));
            $maxAffordable = max(0, ($d['income'] * $maxDsr / 100) - $d['installments']);

            $result = [
                'product' => $p,
                'installment' => $installment,
                'checks' => $checks,
                'eligible' => $passed === count($checks),
                'score' => round($passed / count($checks) * 100),
                'max_installment' => $maxAffordable,
            ];
        }

        return view('public.eligibility', compact('products', 'result'));
    }

    public function faq()
    {
        return view('public.faq');
    }

    public function contact()
    {
        $branches = Branch::query()->active()->orderBy('name')->get();

        return view('public.contact', compact('branches'));
    }

    public function terms()
    {
        return view('public.terms');
    }
}
