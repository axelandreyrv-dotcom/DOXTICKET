<?php

namespace App\Services\Mail;

use App\Contracts\Mail\OAuthTokenClient;
use App\Support\Mail\OAuthTokenSet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class OAuthHttpTokenClient implements OAuthTokenClient
{
    public function exchange(string $provider, string $code): OAuthTokenSet
    {
        $config = config("doxticket.oauth.providers.{$provider}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unsupported OAuth provider [{$provider}].");
        }

        return $this->postTokenRequest($provider, $config, [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri'],
            'code' => $code,
        ]);
    }

    public function refresh(string $provider, string $refreshToken): OAuthTokenSet
    {
        $config = config("doxticket.oauth.providers.{$provider}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unsupported OAuth provider [{$provider}].");
        }

        return $this->postTokenRequest($provider, $config, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function postTokenRequest(string $provider, array $config, array $payload): OAuthTokenSet
    {
        $response = Http::asForm()
            ->timeout(15)
            ->post($this->tokenEndpoint($provider, $config), [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                ...$payload,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'OAuth token exchange failed.');
        }

        $payload = $response->json();
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('OAuth token response did not include an access token.');
        }

        $expiresIn = max(0, (int) ($payload['expires_in'] ?? 0));
        $scope = $payload['scope'] ?? '';

        return new OAuthTokenSet(
            accessToken: $accessToken,
            refreshToken: is_string($payload['refresh_token'] ?? null) ? $payload['refresh_token'] : null,
            expiresAt: Carbon::now()->addSeconds($expiresIn),
            scopes: $scope === '' ? ($config['scopes'] ?? []) : explode(' ', (string) $scope),
            providerUserId: null,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function tokenEndpoint(string $provider, array $config): string
    {
        if ($provider === 'microsoft365') {
            $tenant = trim((string) ($config['tenant'] ?? 'organizations')) ?: 'organizations';

            return "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";
        }

        return (string) $config['token_endpoint'];
    }
}
