<?php

namespace App\Jobs\Mail;

use App\Models\MailAccount;
use App\Services\Mail\OAuthTokenRefresher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueableJob;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class RefreshOAuthMailAccountsJob implements ShouldQueue
{
    use QueueableJob;

    public function __construct()
    {
        $this->onQueue('mail');
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('oauth-mail-token-refresh'))->releaseAfter(300),
        ];
    }

    public function handle(OAuthTokenRefresher $refresher): void
    {
        MailAccount::withoutTenant()
            ->where('is_active', true)
            ->whereIn('provider', ['gmail', 'microsoft365'])
            ->orderBy('id')
            ->each(fn (MailAccount $account) => $refresher->refreshIfNeeded($account));
    }
}
