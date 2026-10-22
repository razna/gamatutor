@props(['title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <x-logo class="h-9 w-9" />
            <div class="leading-tight">
                <div class="text-lg font-extrabold text-brand-900">SiKredit</div>
                <div class="text-[11px] font-medium text-slate-500">Layanan Kredit Digital</div>
            </div>
        </a>
        <nav class="hidden items-center gap-1 text-sm font-medium lg:flex">
            @foreach ([['home','Beranda'],['products','Produk Kredit'],['simulation','Simulasi'],['eligibility','Cek Kelayakan'],['faq','FAQ'],['contact','Kontak']] as [$r, $l])
                <a href="{{ route($r) }}" class="rounded-lg px-3 py-2 {{ request()->routeIs($r.'*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:text-brand-800' }}">{{ $l }}</a>
            @endforeach
        </nav>
        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ auth()->user()->homeRoute() }}" class="btn-primary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-secondary">Masuk</a>
                <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
            @endauth
        </div>
        <button @click="open = !open" class="rounded-lg p-2 text-slate-600 lg:hidden" aria-label="Menu">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>
    <div x-show="open" x-cloak class="border-t border-slate-100 bg-white px-4 py-3 lg:hidden">
        @foreach ([['home','Beranda'],['products','Produk Kredit'],['simulation','Simulasi'],['eligibility','Cek Kelayakan'],['faq','FAQ'],['contact','Kontak']] as [$r, $l])
            <a href="{{ route($r) }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ $l }}</a>
        @endforeach
        <div class="mt-2 flex gap-2">
            @auth
                <a href="{{ auth()->user()->homeRoute() }}" class="btn-primary flex-1">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-secondary flex-1">Masuk</a>
                <a href="{{ route('register') }}" class="btn-primary flex-1">Daftar</a>
            @endauth
        </div>
    </div>
</header>

<main>
    @if (session('success') || session('warning') || $errors->any())
        <div class="mx-auto max-w-7xl px-4 pt-4"><x-flash /></div>
    @endif
    {{ $slot }}
</main>

<footer class="mt-16 bg-brand-950 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-4">
        <div class="md:col-span-2">
            <div class="flex items-center gap-2"><x-logo class="h-8 w-8" /><span class="text-lg font-bold text-white">SiKredit Bank</span></div>
            <p class="mt-3 max-w-md text-sm text-slate-400">Sistem informasi pelayanan pengajuan kredit yang cepat, transparan, dan aman. Ajukan kredit kapan saja, pantau prosesnya secara real-time.</p>
            <p class="mt-4 text-xs text-slate-500">Bank berizin dan diawasi oleh Otoritas Jasa Keuangan (OJK) serta merupakan peserta penjaminan LPS.</p>
        </div>
        <div>
            <div class="font-semibold text-white">Layanan</div>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('products') }}" class="hover:text-white">Produk Kredit</a></li>
                <li><a href="{{ route('simulation') }}" class="hover:text-white">Simulasi Kredit</a></li>
                <li><a href="{{ route('eligibility') }}" class="hover:text-white">Cek Kelayakan</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-white">Syarat & Ketentuan</a></li>
            </ul>
        </div>
        <div>
            <div class="font-semibold text-white">Hubungi Kami</div>
            <ul class="mt-3 space-y-2 text-sm">
                <li>Call Center 1500-123</li>
                <li>layanan@sikredit.test</li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">Lokasi Cabang</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10 py-4 text-center text-xs text-slate-500">&copy; {{ date('Y') }} SiKredit Bank. Hak cipta dilindungi.</div>
</footer>
</body>
</html>
