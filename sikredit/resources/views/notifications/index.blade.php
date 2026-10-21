<x-layouts.app title="Notifikasi">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Semua Notifikasi</div>
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-secondary btn-sm">Tandai semua dibaca</button></form>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($notifications as $n)
                <li>
                    <a href="{{ route('notifications.open', $n->id) }}" class="flex gap-4 px-5 py-4 hover:bg-slate-50 {{ $n->read_at ? '' : 'bg-brand-50/50' }}">
                        <div class="mt-1 h-2.5 w-2.5 flex-none rounded-full {{ $n->read_at ? 'bg-slate-200' : 'bg-brand-600' }}"></div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-800">{{ $n->data['subject'] ?? 'Notifikasi' }}</div>
                            <div class="text-sm text-slate-600">{{ $n->data['body'] ?? '' }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $n->created_at->locale('id')->diffForHumans() }}</div>
                        </div>
                    </a>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">Belum ada notifikasi.</li>
            @endforelse
        </ul>
        <div class="p-4">{{ $notifications->links() }}</div>
    </div>
</x-layouts.app>
