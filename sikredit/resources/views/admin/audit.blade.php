<x-layouts.app title="Audit Trail" subtitle="Rekam jejak aktivitas pengguna (login, perubahan data, keputusan kredit)">
    <div class="card">
        <form class="grid gap-3 border-b border-slate-100 p-4 md:grid-cols-5">
            <input name="q" value="{{ request('q') }}" placeholder="Cari deskripsi / pengguna" class="form-input md:col-span-2">
            <select name="action" class="form-input"><option value="">Semua aksi</option>@foreach (['auth' => 'Autentikasi', 'application' => 'Pengajuan', 'document' => 'Dokumen', 'analysis' => 'Analisis', 'legal' => 'Legal', 'loan' => 'Pinjaman', 'user' => 'Pengguna', 'master' => 'Master data', 'settings' => 'Pengaturan', 'report' => 'Laporan'] as $k => $v)<option value="{{ $k }}" @selected(request('action') === $k)>{{ $v }}</option>@endforeach</select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-input">
            <button class="btn-secondary">Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th>Objek</th><th>IP</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach ($logs as $log)
                    <tr><td class="text-xs">{{ $log->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $log->user?->name ?? '-' }}</td><td><span class="badge bg-slate-100 font-mono text-slate-700">{{ $log->action }}</span></td>
                        <td class="max-w-md whitespace-normal">{{ $log->description }}</td><td class="text-xs">{{ $log->subject_type }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</td><td class="text-xs">{{ $log->ip_address }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
