<?php

namespace App\Services\Mail;

use Illuminate\Session\Store;
use Illuminate\Support\Str;

class OAuthStateStore
{
    private const SESSION_KEY = 'doxticket.mail_oauth_states';

    public function __construct(private readonly Store $session) {}

    public function create(string $provider, int $companyId): string
    {
        $state = Str::random(64);
        $states = $this->session->get(self::SESSION_KEY, []);

        $states[$state] = [
            'provider' => $provider,
            'company_id' => $companyId,
            'expires_at' => now()->addMinutes((int) config('doxticket.oauth.state_ttl_minutes', 10))->timestamp,
        ];

        $this->session->put(self::SESSION_KEY, $states);

        return $state;
    }

    /**
     * @return array{provider: string, company_id: int, expires_at: int}|null
     */
    public function consume(string $state, string $provider, int $companyId): ?array
    {
        $states = $this->session->get(self::SESSION_KEY, []);
        $payload = $states[$state] ?? null;

        unset($states[$state]);
        $this->session->put(self::SESSION_KEY, $states);

        if (! is_array($payload)) {
            return null;
        }

        if (($payload['provider'] ?? null) !== $provider || (int) ($payload['company_id'] ?? 0) !== $companyId) {
            return null;
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return [
            'provider' => (string) $payload['provider'],
            'company_id' => (int) $payload['company_id'],
            'expires_at' => (int) $payload['expires_at'],
        ];
    }
}
