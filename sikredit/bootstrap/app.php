<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        // Di belakang proxy (mis. GitHub Codespaces) set TRUSTED_PROXIES=* agar URL & HTTPS terdeteksi benar.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'staff' => \App\Http\Middleware\EnsureStaff::class,
            'active' => \App\Http\Middleware\EnsureActiveUser::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => auth()->user()->homeRoute());
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
