<x-layouts.auth title="Daftar">
    <h1 class="text-2xl font-bold">Daftar akun nasabah</h1>
    <p class="mt-1 text-sm text-slate-500">Gratis dan hanya butuh 1 menit.</p>
    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <x-field label="Nama lengkap (sesuai KTP)" name="name" required />
        <x-field label="Email" name="email" type="email" required />
        <x-field label="Nomor HP" name="phone" required placeholder="081234567890" />
        <x-field label="Kata sandi" name="password" type="password" required hint="Minimal 8 karakter, kombinasi huruf dan angka." />
        <x-field label="Ulangi kata sandi" name="password_confirmation" type="password" required />
        <label class="flex items-start gap-2 text-sm text-slate-600">
            <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600" @checked(old('terms'))>
            <span>Saya menyetujui <a href="{{ route('terms') }}" target="_blank" class="text-brand-700 underline">syarat & ketentuan serta kebijakan privasi</a>.</span>
        </label>
        <button class="btn-primary w-full py-2.5">Daftar</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Masuk</a></p>
</x-layouts.auth>
