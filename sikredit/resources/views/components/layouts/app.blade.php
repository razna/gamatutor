@props(['title' => null, 'subtitle' => null])
@php
    use App\Enums\Role;
    $user = auth()->user();
    $r = $user->role;
    $unread = $user->unreadNotifications()->count();
    $menu = $user->isStaff()
        ? array_values(array_filter([
            ['admin.dashboard', 'Dashboard', 'home', true],
            ['admin.applications.index', 'Pengajuan Kredit', 'inbox', true],
            ['admin.loans.index', 'Pinjaman Aktif', 'wallet', true],
            ['admin.collection', 'Collection', 'alert', in_array($r, [Role::Collection, Role::KepalaCabang, Role::Komite, Role::Admin])],
            ['admin.reports', 'Laporan', 'chart', in_array($r, [Role::KepalaCabang, Role::Komite, Role::Collection, Role::Admin])],
            ['admin.tickets.index', 'Pengaduan', 'chat', in_array($r, [Role::CustomerService, Role::Admin])],
        ], fn ($m) => $m[3]))
        : [
            ['customer.dashboard', 'Dashboard', 'home', true],
            ['customer.applications.index', 'Pengajuan Saya', 'inbox', true],
            ['customer.loans.index', 'Pinjaman & Angsuran', 'wallet', true],
            ['customer.tickets.index', 'Pengaduan', 'chat', true],
            ['customer.profile.edit', 'Profil Saya', 'user', true],
        ];
    $masterMenu = $r === Role::Admin ? [
        ['admin.products.index', 'Produk Kredit'], ['admin.branches.index', 'Cabang'], ['admin.document-types.index', 'Jenis Dokumen'],
        ['admin.holidays.index', 'Hari Libur'], ['admin.users.index', 'Pengguna & Role'], ['admin.settings.edit', 'Pengaturan Sistem'], ['admin.audit', 'Audit Trail'],
    ] : [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased" x-data="{ sidebar: false }">
<div class="flex min-h-screen lg:bg-[linear-gradient(to_right,#142356_16rem,transparent_16rem)]">
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-brand-950 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 no-print">
        <a href="{{ route('home') }}" class="flex items-center gap-2 px-5 py-5">
            <x-logo class="h-9 w-9" />
            <div>
                <div class="text-lg font-extrabold text-white">SiKredit</div>
                <div class="text-[11px] text-slate-400">{{ $user->isStaff() ? 'Portal Petugas' : 'Portal Nasabah' }}</div>
            </div>
        </a>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-6">
            @foreach ($menu as [$route, $label, $icon])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs(str_replace('.index', '', $route).'*') ? 'active' : '' }}">
                    <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                <x-icon name="bell" class="h-5 w-5" /> Notifikasi
                @if ($unread) <span class="ml-auto rounded-full bg-gold-500 px-2 text-xs font-bold text-brand-950">{{ $unread }}</span> @endif
            </a>
            @if ($masterMenu)
                <div class="px-3 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Administrasi</div>
                @foreach ($masterMenu as [$route, $label])
                    <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs(str_replace(['.index', '.edit'], '', $route).'*') ? 'active' : '' }}">
                        <x-icon name="cog" class="h-4 w-4" /> {{ $label }}
                    </a>
                @endforeach
            @endif
        </nav>
        <div class="border-t border-white/10 p-4">
            <div class="text-sm font-semibold text-white">{{ $user->name }}</div>
            <div class="text-xs text-slate-400">{{ $r->label() }}{{ $user->branch ? ' · '.$user->branch->name : '' }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
                <button class="w-full rounded-lg bg-white/10 px-3 py-2 text-left text-sm text-slate-200 hover:bg-white/20">Keluar</button>
            </form>
        </div>
    </aside>
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-40 bg-black/40 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:px-8 no-print">
            <button @click="sidebar = true" class="rounded-lg p-1.5 text-slate-600 lg:hidden" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                @isset($subtitle)<p class="truncate text-xs text-slate-500">{{ $subtitle }}</p>@endisset
            </div>
            {{ $actions ?? '' }}
            <a href="{{ route('notifications.index') }}" class="relative rounded-full p-2 text-slate-500 hover:bg-slate-100" aria-label="Notifikasi">
                <x-icon name="bell" class="h-5 w-5" />
                @if ($unread)<span class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-rose-500"></span>@endif
            </a>
        </header>
        <main class="flex-1 p-4 lg:p-8">
            <x-flash />
            {{ $slot }}
        </main>
        <footer class="px-8 pb-6 text-xs text-slate-400 no-print">&copy; {{ date('Y') }} SiKredit Bank · Sesi berakhir otomatis setelah {{ config('session.lifetime') }} menit tidak aktif.</footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
