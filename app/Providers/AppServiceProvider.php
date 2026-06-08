<?php

namespace App\Providers;

use App\Contracts\Mail\ImapConnection;
use App\Contracts\Mail\MailAccountTester;
use App\Contracts\Mail\MailboxClient;
use App\Contracts\Mail\OAuthTokenClient;
use App\Services\Mail\ImapMailboxClient;
use App\Services\Mail\NativeImapConnection;
use App\Services\Mail\OAuthHttpTokenClient;
use App\Services\Mail\OAuthMailboxClient;
use App\Services\Mail\RoutingMailboxClient;
use App\Services\Mail\SupportMailAccountTester;
use App\Support\Tenant\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
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
        $this->app->bind(ImapConnection::class, NativeImapConnection::class);
        $this->app->bind(MailboxClient::class, fn (Application $app): RoutingMailboxClient => new RoutingMailboxClient(
            $app->make(ImapMailboxClient::class),
            $app->make(OAuthMailboxClient::class),
        ));
        $this->app->bind(ImapMailboxClient::class);
        $this->app->bind(MailAccountTester::class, SupportMailAccountTester::class);
        $this->app->bind(OAuthTokenClient::class, OAuthHttpTokenClient::class);
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
