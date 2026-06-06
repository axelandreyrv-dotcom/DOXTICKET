<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use App\Support\Mail\OAuthTokenSet;
use InvalidArgumentException;

class OAuthTokenStore
{
    public function store(MailAccount $account, OAuthTokenSet $tokens): MailAccount
    {
        if (! $account->usesOAuth()) {
            throw new InvalidArgumentException('OAuth tokens can only be stored for Gmail or Microsoft 365 mail accounts.');
        }

        $data = [
            'oauth_access_token' => $tokens->accessToken,
            'oauth_expires_at' => $tokens->expiresAt,
            'oauth_scopes' => array_values(array_unique($tokens->scopes)),
            'oauth_provider_user_id' => $tokens->providerUserId,
            'oauth_connected_at' => now(),
            'password_encrypted' => null,
            'last_error' => null,
            'is_active' => true,
        ];

        if ($tokens->refreshToken !== null && $tokens->refreshToken !== '') {
            $data['oauth_refresh_token'] = $tokens->refreshToken;
        }

        $account->forceFill($data)->save();

        return $account->refresh();
    }
}
