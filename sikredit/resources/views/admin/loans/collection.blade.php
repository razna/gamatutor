@php use App\Enums\Collectibility; @endphp
<x-layouts.app title="Collection" subtitle="Pemantauan tunggakan & kolektibilitas kredit">
    <div class="grid gap-4 sm:grid-cols-5">
        @foreach (Collectibility::cases() as $k)
            <a href="{{ route('admin.collection', ['kol' => $k->value]) }}" class="card p-4 hover:shadow-md {{ request('kol') == $k->value ? 'ring-2 ring-brand-500' : '' }}">
                <span class="badge {{ $k->color() }}">Kol {{ $k->value }}</span>
                <div class="mt-2 text-sm font-medium">{{ $k->label() }}</div>
                <div class="text-2xl font-bold">{{ $summary[$k->value]->total ?? 0 }}</div>
                <div class="text-xs text-slate-500">{{ rupiah($summary[$k->value]->outstanding ?? 0) }}</div>
            </a>
        @endforeach
    </div>
    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><div class="card-title">Debitur Menunggak</div>
                <form class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Cari…" class="form-input py-1.5"><input type="hidden" name="kol" value="{{ request('kol') }}"><button class="btn-secondary btn-sm">Cari</button></form></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Nasabah</th><th>No. Kredit</th><th>Telepon</th><th class="text-right">Outstanding</th><th>Hari</th><th>Kol</th><th>Tindakan terakhir</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($loans as $l)
                        @php $kol = $l->collectibilityEnum(); $note = $l->collectionNotes->first(); @endphp
                        <tr class="cursor-pointer" onclick="location='{{ route('admin.loans.show', $l) }}'">
                            <td class="font-medium">{{ $l->customer->user->name }}</td><td class="text-brand-700">{{ $l->number }}</td><td>{{ $l->customer->user->phone }}</td>
                            <td class="text-right">{{ rupiah($l->outstanding_principal) }}</td><td class="font-semibold text-rose-600">{{ $l->days_past_due }}</td>
                            <td><span class="badge {{ $kol->color() }}">{{ $kol->value }}</span></td>
                            <td class="text-xs">{{ $note ? \App\Models\CollectionNote::ACTIONS[$note->action].' · '.$note->created_at->locale('id')->diffForHumans() : 'Belum ditindaklanjuti' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-500">Tidak ada kredit menunggak. 🎉</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $loans->links() }}</div>
        </div>
        <div class="card h-fit">
            <div class="card-header"><div class="card-title">Janji Bayar 7 Hari ke Depan</div></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($promises as $p)
                    <li class="px-5 py-3"><a href="{{ route('admin.loans.show', $p->loan_application_id) }}" class="font-medium text-brand-700">{{ $p->application->customer->user->name }}</a>
                        <div class="text-xs text-slate-500">{{ tanggal($p->promise_date) }} · {{ rupiah($p->promise_amount) }}</div></li>
                @empty
                    <li class="px-5 py-6 text-center text-slate-500">Tidak ada janji bayar.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.app>
