<?php

namespace App\Support\Mail;

use Illuminate\Support\Carbon;

final readonly class OAuthTokenSet
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public Carbon $expiresAt,
        public array $scopes,
        public ?string $providerUserId,
    ) {}
}
