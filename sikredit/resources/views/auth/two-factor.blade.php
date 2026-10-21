<x-layouts.auth title="Verifikasi OTP">
    <h1 class="text-2xl font-bold">Verifikasi dua langkah</h1>
    <p class="mt-1 text-sm text-slate-500">Kami telah mengirim kode OTP 6 digit ke email Anda. Kode berlaku 10 menit.</p>
    <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-6 space-y-4">
        @csrf
        <x-field label="Kode OTP" name="code" inputmode="numeric" maxlength="6" required autofocus autocomplete="one-time-code" />
        <button class="btn-primary w-full py-2.5">Verifikasi</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf<button class="text-sm text-slate-500 hover:underline">Batal & keluar</button></form>
</x-layouts.auth>
