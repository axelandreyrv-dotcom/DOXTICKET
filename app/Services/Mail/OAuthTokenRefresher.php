<?php

namespace App\Services\Mail;

use App\Contracts\Mail\OAuthTokenClient;
use App\Models\MailAccount;
use Illuminate\Support\Str;
use Throwable;

class OAuthTokenRefresher
{
    public function __construct(
        private readonly OAuthTokenClient $tokenClient,
        private readonly OAuthTokenStore $tokenStore,
    ) {}

    public function refreshIfNeeded(MailAccount $account): bool
    {
        if (! $account->usesOAuth() || blank($account->oauth_refresh_token)) {
            return false;
        }

        if ($account->oauth_expires_at !== null && $account->oauth_expires_at->greaterThan(now()->addMinutes(5))) {
            return false;
        }

        try {
            $tokens = $this->tokenClient->refresh($account->provider, $account->oauth_refresh_token);
            $this->tokenStore->store($account, $tokens);
        } catch (Throwable $exception) {
            $account->forceFill([
                'last_error' => $this->safeRefreshMessage($exception->getMessage(), $account),
            ])->save();

            return false;
        }

        return true;
    }

    private function safeRefreshMessage(?string $message, MailAccount $account): string
    {
        $message = Str::limit($message ?: 'No se pudo renovar el token OAuth.', 500, '');
        $secrets = array_filter([
            $account->oauth_access_token,
            $account->oauth_refresh_token,
            config('doxticket.oauth.providers.'.$account->provider.'.client_secret'),
        ]);

        foreach ($secrets as $secret) {
            $message = str_replace((string) $secret, '[redacted]', $message);
        }

        return preg_replace('/(refresh_token|access_token|client_secret|password|token|secret|authorization)(=|:)\S+/i', '$1$2[redacted]', $message) ?? 'No se pudo renovar el token OAuth.';
    }
}
