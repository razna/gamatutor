<x-layouts.auth title="Atur Ulang Kata Sandi">
    <h1 class="text-2xl font-bold">Atur ulang kata sandi</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field label="Email" name="email" type="email" :value="$email" required />
        <x-field label="Kata sandi baru" name="password" type="password" required />
        <x-field label="Ulangi kata sandi baru" name="password_confirmation" type="password" required />
        <button class="btn-primary w-full py-2.5">Simpan kata sandi</button>
    </form>
</x-layouts.auth>
