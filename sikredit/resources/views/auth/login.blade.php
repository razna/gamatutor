<x-layouts.auth title="Masuk">
    <h1 class="text-2xl font-bold">Masuk ke akun Anda</h1>
    <p class="mt-1 text-sm text-slate-500">Nasabah maupun petugas bank masuk melalui halaman ini.</p>
    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <x-field label="Email" name="email" type="email" required autofocus autocomplete="username" />
        <x-field label="Kata sandi" name="password" type="password" required autocomplete="current-password" />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600"> Ingat saya</label>
            <a href="{{ route('password.request') }}" class="text-brand-700 hover:underline">Lupa kata sandi?</a>
        </div>
        <button class="btn-primary w-full py-2.5">Masuk</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Daftar sebagai nasabah</a></p>
    @if (app()->environment('local'))
        <div class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">Akun demo: lihat README (kata sandi <code>password123</code>).</div>
    @endif
</x-layouts.auth>
