<?php

namespace App\Providers;

use App\Contracts\Mail\MailboxClient;
use App\Services\Mail\ImapMailboxClient;
use App\Support\Tenant\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->bind(MailboxClient::class, ImapMailboxClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $emailKey = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($request->ip().'|'.$emailKey),
                Limit::perMinutes(5, 20)->by($request->ip()),
            ];
        });

        RateLimiter::for('setup', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
