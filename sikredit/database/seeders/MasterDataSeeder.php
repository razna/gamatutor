<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CreditProduct;
use App\Models\DocumentType;
use App\Models\Holiday;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['JKT01', 'KC Jakarta Sudirman', 'Jl. Jend. Sudirman Kav. 21', 'Jakarta Selatan', '021-5701234'],
            ['BDG01', 'KC Bandung Asia Afrika', 'Jl. Asia Afrika No. 88', 'Bandung', '022-4231234'],
            ['SBY01', 'KC Surabaya Basuki Rahmat', 'Jl. Basuki Rahmat No. 120', 'Surabaya', '031-5321234'],
            ['YOG01', 'KC Yogyakarta Malioboro', 'Jl. Malioboro No. 45', 'Yogyakarta', '0274-561234'],
        ] as [$code, $name, $addr, $city, $phone]) {
            Branch::query()->create(compact('code', 'name') + ['address' => $addr, 'city' => $city, 'phone' => $phone]);
        }

        $docs = [];
        foreach ([
            ['KTP', 'KTP Pemohon', 'e-KTP yang masih berlaku'],
            ['KTP_PASANGAN', 'KTP Pasangan', 'Bagi pemohon yang sudah menikah'],
            ['KK', 'Kartu Keluarga', null],
            ['NPWP', 'NPWP', 'Wajib untuk plafon di atas Rp50 juta'],
            ['SELFIE', 'Foto Selfie dengan KTP', 'Untuk verifikasi identitas (e-KYC)'],
            ['SLIP_GAJI', 'Slip Gaji 3 Bulan Terakhir', null],
            ['SK_KERJA', 'Surat Keterangan Kerja / SK Pengangkatan', null],
            ['REK_KORAN', 'Rekening Koran 6 Bulan', null],
            ['NIB', 'NIB / SIUP / Surat Keterangan Usaha', null],
            ['LAPKEU', 'Laporan Keuangan Usaha', 'Neraca dan laba rugi 1 tahun terakhir'],
            ['SHM', 'Sertifikat Tanah (SHM/SHGB) & IMB/PBG', null],
            ['PBB', 'SPPT PBB Tahun Terakhir', null],
            ['BPKB', 'BPKB & STNK Kendaraan', null],
            ['SPK_DEALER', 'SPK / Invoice Dealer', 'Untuk pembelian kendaraan'],
            ['PPJB', 'PPJB / Surat Pemesanan Rumah', 'Untuk pembelian properti'],
        ] as [$code, $name, $desc]) {
            $docs[$code] = DocumentType::query()->create(['code' => $code, 'name' => $name, 'description' => $desc])->id;
        }

        $products = [
            ['KPR-01', 'KPR Griya Impian', 'KPR', 'Kredit pemilikan rumah baru maupun second dengan tenor hingga 20 tahun dan bunga kompetitif.', 7.75, 'anuitas', 100e6, 5e9, 12, 240, 1, 2500000, 0.1, true, 80,
                ['KTP' => 1, 'KTP_PASANGAN' => 0, 'KK' => 1, 'NPWP' => 1, 'SLIP_GAJI' => 1, 'REK_KORAN' => 1, 'SHM' => 1, 'PBB' => 1, 'PPJB' => 1]],
            ['KKB-01', 'Kredit Kendaraan Bermotor', 'KKB', 'Pembiayaan mobil dan motor baru maupun bekas dengan proses cepat.', 8.5, 'flat', 20e6, 1e9, 12, 60, 1, 1000000, 0.15, true, 85,
                ['KTP' => 1, 'KK' => 1, 'NPWP' => 0, 'SLIP_GAJI' => 1, 'REK_KORAN' => 1, 'BPKB' => 0, 'SPK_DEALER' => 1]],
            ['KMK-01', 'Kredit Modal Kerja Usaha', 'KMK', 'Tambahan modal kerja untuk memperlancar operasional dan ekspansi usaha.', 10.5, 'efektif', 50e6, 5e9, 12, 60, 1, 1500000, 0.1, true, 75,
                ['KTP' => 1, 'KK' => 1, 'NPWP' => 1, 'NIB' => 1, 'LAPKEU' => 1, 'REK_KORAN' => 1, 'SHM' => 1]],
            ['KI-01', 'Kredit Investasi', 'KI', 'Pembiayaan pembelian aset tetap usaha seperti mesin, kendaraan operasional, dan bangunan.', 10.0, 'anuitas', 100e6, 10e9, 24, 120, 1, 2500000, 0.1, true, 70,
                ['KTP' => 1, 'KK' => 1, 'NPWP' => 1, 'NIB' => 1, 'LAPKEU' => 1, 'REK_KORAN' => 1, 'SHM' => 1]],
            ['KTA-01', 'Kredit Tanpa Agunan', 'KTA', 'Pinjaman tunai tanpa jaminan untuk karyawan dan profesional dengan proses kilat.', 13.5, 'anuitas', 5e6, 300e6, 12, 60, 2, 500000, 0.15, false, 0,
                ['KTP' => 1, 'KK' => 1, 'NPWP' => 0, 'SELFIE' => 1, 'SLIP_GAJI' => 1, 'SK_KERJA' => 1, 'REK_KORAN' => 1]],
            ['KUR-01', 'Kredit Usaha Rakyat (KUR) Mikro', 'KUR', 'Program pemerintah untuk UMKM dengan bunga bersubsidi 6% efektif per tahun.', 6.0, 'efektif', 10e6, 100e6, 12, 60, 0, 0, 0.05, false, 0,
                ['KTP' => 1, 'KK' => 1, 'NIB' => 1, 'SELFIE' => 1]],
            ['MULTI-01', 'Kredit Multiguna', 'MULTIGUNA', 'Dana tunai untuk renovasi, pendidikan, kesehatan, atau kebutuhan lain dengan agunan sertifikat/BPKB.', 11.0, 'anuitas', 25e6, 2e9, 12, 120, 1, 1000000, 0.1, true, 70,
                ['KTP' => 1, 'KK' => 1, 'NPWP' => 1, 'SLIP_GAJI' => 1, 'REK_KORAN' => 1, 'SHM' => 0, 'BPKB' => 0]],
        ];
        $requirements = "• WNI berusia 21–65 tahun (maks. 70 tahun saat lunas)\n• Karyawan tetap min. 1 tahun / usaha berjalan min. 2 tahun\n• Penghasilan memadai dengan DSR maksimal 40%\n• Tidak memiliki kredit bermasalah (SLIK OJK)";

        foreach ($products as [$code, $name, $cat, $desc, $rate, $method, $min, $max, $minT, $maxT, $prov, $admin, $pen, $coll, $ltv, $docMap]) {
            $p = CreditProduct::query()->create([
                'code' => $code, 'name' => $name, 'category' => $cat, 'description' => $desc, 'interest_rate' => $rate, 'interest_method' => $method,
                'min_amount' => $min, 'max_amount' => $max, 'min_tenor' => $minT, 'max_tenor' => $maxT, 'provision_fee' => $prov, 'admin_fee' => $admin,
                'penalty_rate' => $pen, 'requires_collateral' => $coll, 'max_ltv' => $ltv, 'requirements' => $requirements,
            ]);
            $p->documentTypes()->sync(collect($docMap)->mapWithKeys(fn ($req, $c) => [$docs[$c] => ['is_required' => (bool) $req]])->all());
        }

        $year = now()->year;
        foreach ([
            ['01-01', 'Tahun Baru Masehi'], ['05-01', 'Hari Buruh Internasional'], ['06-01', 'Hari Lahir Pancasila'],
            ['08-17', 'Hari Kemerdekaan RI'], ['12-25', 'Hari Raya Natal'], ['12-26', 'Cuti Bersama Natal'],
        ] as [$md, $name]) {
            foreach ([$year - 1, $year, $year + 1] as $y) {
                Holiday::query()->create(['date' => "{$y}-{$md}", 'name' => $name]);
            }
        }

        foreach (Setting::DEFAULTS as $k => $v) {
            Setting::put($k, $v);
        }

        foreach ([
            'status_changed' => ['Status pengajuan {nomor}: {status}', 'Yth. {nama}, pengajuan kredit Anda nomor {nomor} kini berstatus "{status}". Catatan petugas: {catatan}'],
            'installment_reminder' => ['Pengingat angsuran ke-{angsuran_ke} — {nomor}', 'Yth. {nama}, angsuran ke-{angsuran_ke} sebesar {jumlah} akan jatuh tempo pada {jatuh_tempo}. Pastikan saldo rekening Anda mencukupi.'],
            'installment_overdue' => ['Angsuran {nomor} telah lewat jatuh tempo', 'Yth. {nama}, angsuran ke-{angsuran_ke} sebesar {jumlah} telah melewati jatuh tempo {jatuh_tempo}. Segera lakukan pembayaran untuk menghindari denda dan penurunan kolektibilitas.'],
            'payment_received' => ['Pembayaran diterima — {kuitansi}', 'Terima kasih {nama}, pembayaran sebesar {jumlah} untuk kredit {nomor} telah kami terima dengan nomor kuitansi {kuitansi}.'],
        ] as $event => [$subject, $body]) {
            NotificationTemplate::query()->create(compact('event', 'subject', 'body'));
        }
    }
}
