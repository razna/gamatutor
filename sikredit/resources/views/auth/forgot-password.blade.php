<x-layouts.auth title="Lupa Kata Sandi">
    <h1 class="text-2xl font-bold">Lupa kata sandi</h1>
    <p class="mt-1 text-sm text-slate-500">Masukkan email Anda, kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <x-field label="Email" name="email" type="email" required />
        <button class="btn-primary w-full py-2.5">Kirim tautan</button>
    </form>
    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="text-brand-700 hover:underline">Kembali ke halaman masuk</a></p>
</x-layouts.auth>
