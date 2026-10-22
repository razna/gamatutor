<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus as S;
use App\Enums\Collectibility;
use App\Http\Controllers\Controller;
use App\Models\ApplicationLog;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CreditProduct;
use App\Models\LoanApplication;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const TYPES = [
        'applications' => 'Laporan Pengajuan Kredit',
        'decisions' => 'Laporan Persetujuan & Penolakan',
        'disbursements' => 'Laporan Pencairan',
        'outstanding' => 'Laporan Outstanding Kredit',
        'arrears' => 'Laporan Tunggakan & Kolektibilitas',
        'payments' => 'Laporan Penerimaan Angsuran',
        'performance' => 'Laporan Kinerja Petugas',
    ];

    public function index(Request $request)
    {
        $type = array_key_exists($request->input('type'), self::TYPES) ? $request->input('type') : 'applications';
        [$columns, $rows, $totals] = $this->build($type, $request);

        if ($request->input('export') === 'csv') {
            return $this->csv($type, $columns, $rows, $totals);
        }

        $view = $request->input('export') === 'print' ? 'admin.reports.print' : 'admin.reports.index';

        return view($view, [
            'type' => $type,
            'title' => self::TYPES[$type],
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
            'products' => CreditProduct::query()->orderBy('name')->get(),
            'branches' => Branch::query()->orderBy('name')->get(),
            'from' => $this->from($request),
            'to' => $this->to($request),
        ]);
    }

    private function from(Request $r): string
    {
        return $r->input('from', now()->startOfYear()->toDateString());
    }

    private function to(Request $r): string
    {
        return $r->input('to', today()->toDateString());
    }

    private function base(Request $r)
    {
        return LoanApplication::query()->visibleTo($r->user())
            ->with('customer.user', 'product', 'branch')
            ->when($r->input('product'), fn ($q, $p) => $q->where('credit_product_id', $p))
            ->when($r->input('branch'), fn ($q, $b) => $q->where('branch_id', $b));
    }

    /** @return array{0: array, 1: Collection, 2: array} */
    private function build(string $type, Request $r): array
    {
        $from = $this->from($r);
        $to = $this->to($r);

        switch ($type) {
            case 'decisions':
                $rows = $this->base($r)->whereNotNull('decided_at')->whereBetween('decided_at', [$from, $to.' 23:59:59'])->with('approver')->latest('decided_at')->get()
                    ->map(fn ($a) => [$a->number, $a->customer->user->name, $a->product->name, $a->branch->name, rupiah($a->amount), $a->approved_amount && $a->status !== S::Rejected ? rupiah($a->approved_amount) : '-', $a->status === S::Rejected ? 'DITOLAK' : 'DISETUJUI', $a->approver?->name ?? $a->cs?->name ?? '-', tanggal($a->decided_at)]);
                $approved = $rows->where(6, 'DISETUJUI')->count();

                return [['No. Pengajuan', 'Nasabah', 'Produk', 'Cabang', 'Diajukan', 'Disetujui', 'Keputusan', 'Pemutus', 'Tgl Keputusan'], $rows,
                    ['Total keputusan' => $rows->count(), 'Disetujui' => $approved, 'Ditolak' => $rows->count() - $approved, 'Approval rate' => $rows->count() ? round($approved / $rows->count() * 100, 1).'%' : '0%']];

            case 'disbursements':
                $data = $this->base($r)->whereNotNull('disbursed_at')->whereBetween('disbursed_at', [$from, $to])->latest('disbursed_at')->get();
                $rows = $data->map(fn ($a) => [$a->number, $a->contract_number, $a->customer->user->name, $a->product->name, $a->branch->name, tanggal($a->disbursed_at), rupiah($a->approved_amount), rupiah($a->provision_amount + $a->admin_amount), rupiah($a->net_disbursed), $a->approved_tenor.' bln']);

                return [['No. Pengajuan', 'No. Perjanjian', 'Nasabah', 'Produk', 'Cabang', 'Tgl Cair', 'Plafon', 'Provisi+Admin', 'Diterima Nasabah', 'Tenor'], $rows,
                    ['Jumlah debitur' => $data->count(), 'Total plafon' => rupiah($data->sum('approved_amount')), 'Pendapatan provisi & admin' => rupiah($data->sum(fn ($a) => $a->provision_amount + $a->admin_amount))]];

            case 'outstanding':
                $data = $this->base($r)->status(S::Active)->orderByDesc('outstanding_principal')->get();
                $rows = $data->map(fn ($a) => [$a->number, $a->customer->user->name, $a->product->name, $a->branch->name, tanggal($a->disbursed_at), rupiah($a->approved_amount), rupiah($a->outstanding_principal), $a->finalRate().'%', $a->collectibilityEnum()?->label() ?? '-']);
                $npl = $data->filter(fn ($a) => $a->collectibility >= 3)->sum('outstanding_principal');
                $os = $data->sum('outstanding_principal');

                return [['No. Pengajuan', 'Nasabah', 'Produk', 'Cabang', 'Tgl Cair', 'Plafon', 'Outstanding', 'Bunga', 'Kolektibilitas'], $rows,
                    ['Jumlah debitur aktif' => $data->count(), 'Total outstanding' => rupiah($os), 'Outstanding NPL (Kol 3–5)' => rupiah($npl), 'Rasio NPL' => ($os > 0 ? round($npl / $os * 100, 2) : 0).'%']];

            case 'arrears':
                $data = $this->base($r)->status(S::Active)->where('days_past_due', '>', 0)->with('activeInstallments')->orderByDesc('days_past_due')->get();
                $rows = $data->map(fn ($a) => [$a->number, $a->customer->user->name, $a->customer->user->phone, $a->branch->name, $a->days_past_due.' hari', rupiah($a->totalArrears()), rupiah($a->outstanding_principal), $a->collectibilityEnum()->label()]);
                $perKol = collect(Collectibility::cases())->mapWithKeys(fn ($k) => ['Kol '.$k->value.' '.$k->label() => $data->where('collectibility', $k->value)->count()])->all();

                return [['No. Pengajuan', 'Nasabah', 'Telepon', 'Cabang', 'Hari Tunggak', 'Total Tunggakan', 'Outstanding', 'Kolektibilitas'], $rows,
                    ['Debitur menunggak' => $data->count(), 'Total tunggakan' => rupiah($data->sum(fn ($a) => $a->totalArrears()))] + $perKol];

            case 'payments':
                $data = Payment::query()->whereBetween('paid_at', [$from, $to])
                    ->whereHas('application', fn ($q) => $q->visibleTo($r->user())
                        ->when($r->input('product'), fn ($q, $p) => $q->where('credit_product_id', $p))
                        ->when($r->input('branch'), fn ($q, $b) => $q->where('branch_id', $b)))
                    ->with('application.customer.user', 'receiver')->latest('paid_at')->get();
                $rows = $data->map(fn ($p) => [$p->receipt_number, tanggal($p->paid_at), $p->application->number, $p->application->customer->user->name, $p->type === 'payoff' ? 'Pelunasan' : 'Angsuran', rupiah($p->principal_paid), rupiah($p->interest_paid), rupiah($p->penalty_paid), rupiah($p->amount), $p->receiver?->name ?? '-']);

                return [['No. Kuitansi', 'Tanggal', 'No. Kredit', 'Nasabah', 'Jenis', 'Pokok', 'Bunga', 'Denda', 'Total', 'Petugas'], $rows,
                    ['Jumlah transaksi' => $data->count(), 'Pokok' => rupiah($data->sum('principal_paid')), 'Bunga' => rupiah($data->sum('interest_paid')), 'Denda' => rupiah($data->sum('penalty_paid')), 'Total diterima' => rupiah($data->sum('amount'))]];

            case 'performance':
                $logs = ApplicationLog::query()->whereBetween('created_at', [$from, $to.' 23:59:59'])
                    ->whereHas('application', fn ($q) => $q->visibleTo($r->user()))
                    ->whereHas('user', fn ($q) => $q->where('role', '!=', 'nasabah'))
                    ->with('user')->get()->groupBy('user_id');
                $rows = $logs->map(function ($items) {
                    $u = $items->first()->user;

                    return [$u->name, $u->role->label(), $u->branch?->name ?? 'Pusat', $items->count(), $items->where('sla_breached', true)->count(),
                        round(($items->count() - $items->where('sla_breached', true)->count()) / max(1, $items->count()) * 100, 1).'%'];
                })->sortByDesc(3)->values();

                return [['Petugas', 'Peran', 'Cabang', 'Jumlah Tindakan', 'Melewati SLA', 'Kepatuhan SLA'], $rows,
                    ['Total tindakan' => $logs->flatten()->count(), 'Petugas aktif' => $rows->count()]];

            default: // applications
                $data = $this->base($r)->whereNotNull('submitted_at')->whereBetween('submitted_at', [$from, $to.' 23:59:59'])
                    ->when($r->input('status'), fn ($q, $s) => $q->where('status', $s))->latest('submitted_at')->get();
                $rows = $data->map(fn ($a) => [$a->number, tanggal($a->submitted_at), $a->customer->user->name, $a->product->name, $a->branch->name, rupiah($a->amount), $a->tenor.' bln', $a->status->label()]);
                $byStatus = $data->groupBy(fn ($a) => $a->status->label())->map->count()->all();

                return [['No. Pengajuan', 'Tgl Pengajuan', 'Nasabah', 'Produk', 'Cabang', 'Plafon', 'Tenor', 'Status'], $rows,
                    ['Total pengajuan' => $data->count(), 'Total plafon diajukan' => rupiah($data->sum('amount'))] + $byStatus];
        }
    }

    private function csv(string $type, array $columns, Collection $rows, array $totals): StreamedResponse
    {
        $filename = 'laporan-'.$type.'-'.now()->format('Ymd-His').'.csv';
        AuditLog::record('report.export', null, 'Ekspor '.self::TYPES[$type]);

        return response()->streamDownload(function () use ($columns, $rows, $totals, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, [self::TYPES[$type]], ';');
            fputcsv($out, ['Dicetak: '.now()->format('d/m/Y H:i')], ';');
            fputcsv($out, [], ';');
            fputcsv($out, $columns, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_string($v) ? str_replace('Rp ', '', $v) : $v, $row), ';');
            }
            fputcsv($out, [], ';');
            foreach ($totals as $k => $v) {
                fputcsv($out, [$k, $v], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
