<?php

namespace App\Providers;

use App\Models\LoanApplication;
use App\Models\User;
use App\Services\BusinessCalendar;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BusinessCalendar::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::useTailwind();

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?: $r->ip()));

        // Akses melihat pengajuan: pemilik (nasabah) atau petugas di cabang yang sama.
        Gate::define('view-application', function (User $user, LoanApplication $app) {
            if (! $user->isStaff()) {
                return $app->customer?->user_id === $user->id;
            }

            return $user->seesAllBranches() || $user->branch_id === $app->branch_id;
        });
    }
}
