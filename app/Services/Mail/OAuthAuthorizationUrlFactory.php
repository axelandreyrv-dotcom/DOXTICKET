<?php

namespace App\Services\Mail;

use InvalidArgumentException;

class OAuthAuthorizationUrlFactory
{
    public function make(string $provider, string $state): string
    {
        $config = config("doxticket.oauth.providers.{$provider}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unsupported OAuth provider [{$provider}].");
        }

        return match ($provider) {
            'gmail' => $this->googleUrl($config, $state),
            'microsoft365' => $this->microsoftUrl($config, $state),
            default => throw new InvalidArgumentException("Unsupported OAuth provider [{$provider}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function googleUrl(array $config, string $state): string
    {
        return $this->url((string) $config['authorization_endpoint'], [
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $this->scope($config),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function microsoftUrl(array $config, string $state): string
    {
        $tenant = trim((string) ($config['tenant'] ?? 'organizations')) ?: 'organizations';
        $endpoint = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize";

        return $this->url($endpoint, [
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $this->scope($config),
            'state' => $state,
            'response_mode' => 'query',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function url(string $endpoint, array $query): string
    {
        return $endpoint.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function scope(array $config): string
    {
        return implode(' ', array_values(array_unique($config['scopes'] ?? [])));
    }
}
