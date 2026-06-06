<?php

namespace App\Contracts\Mail;

use App\Support\Mail\OAuthTokenSet;

interface OAuthTokenClient
{
    public function exchange(string $provider, string $code): OAuthTokenSet;

    public function refresh(string $provider, string $refreshToken): OAuthTokenSet;
}
