<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            AuditLog::record('auth.failed', null, 'Login gagal: '.$credentials['email']);

            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        $user = $request->user();
        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi administrator.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('auth.login', $user, 'Login berhasil');

        if ($user->isStaff() && (int) Setting::get('staff_two_factor') === 1) {
            $this->sendTwoFactorCode($request, $user);

            return redirect()->route('two-factor.show');
        }

        return redirect()->intended($user->homeRoute());
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], ['phone.regex' => 'Nomor HP tidak valid (contoh: 081234567890).', 'terms.accepted' => 'Anda harus menyetujui syarat & ketentuan serta kebijakan privasi.']);

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => Role::Nasabah,
            ]);
            $user->customer()->create();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('auth.register', $user, 'Registrasi nasabah baru');
        $user->notify(new GeneralNotification(
            'Selamat datang di '.config('app.name'),
            'Akun Anda berhasil dibuat. Lengkapi profil Anda agar dapat mengajukan kredit.',
            route('customer.profile.edit'),
        ));

        return redirect()->route('customer.profile.edit')->with('success', 'Registrasi berhasil. Silakan lengkapi data profil Anda.');
    }

    public function logout(Request $request)
    {
        AuditLog::record('auth.logout', $request->user(), 'Logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ---- Verifikasi dua langkah (OTP email) untuk staf ------------------------------------

    private function sendTwoFactorCode(Request $request, User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $request->session()->put('two_factor_pending', [
            'hash' => Hash::make($code),
            'expires' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);
        $user->notify(new GeneralNotification('Kode verifikasi login', "Kode OTP Anda: {$code}. Berlaku 10 menit. Jangan berikan kode ini kepada siapa pun."));
    }

    public function showTwoFactor(Request $request)
    {
        abort_unless($request->session()->has('two_factor_pending'), 404);

        return view('auth.two-factor');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $pending = $request->session()->get('two_factor_pending');
        abort_unless($pending, 404);

        if ($pending['expires'] < now()->timestamp || $pending['attempts'] >= 5) {
            $request->session()->forget('two_factor_pending');
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => 'Kode OTP kedaluwarsa atau terlalu banyak percobaan. Silakan login ulang.']);
        }

        if (! Hash::check($request->input('code'), $pending['hash'])) {
            $pending['attempts']++;
            $request->session()->put('two_factor_pending', $pending);

            return back()->withErrors(['code' => 'Kode OTP salah.']);
        }

        $request->session()->forget('two_factor_pending');
        AuditLog::record('auth.2fa', $request->user(), 'Verifikasi OTP berhasil');

        return redirect()->intended($request->user()->homeRoute());
    }

    // ---- Lupa kata sandi ------------------------------------------------------------------

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Pesan seragam agar tidak membocorkan email yang terdaftar.
        return back()->with('success', 'Jika email terdaftar, tautan atur ulang kata sandi telah dikirim.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
