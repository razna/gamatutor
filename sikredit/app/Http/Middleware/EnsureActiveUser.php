<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Keluarkan pengguna yang dinonaktifkan dan wajibkan verifikasi 2FA bagi staf bila diaktifkan. */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
        }

        if ($user && $request->session()->has('two_factor_pending')) {
            return redirect()->route('two-factor.show');
        }

        return $next($request);
    }
}
