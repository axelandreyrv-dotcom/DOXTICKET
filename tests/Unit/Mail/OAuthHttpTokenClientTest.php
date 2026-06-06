<?php

namespace Tests\Unit\Mail;

use App\Services\Mail\OAuthHttpTokenClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OAuthHttpTokenClientTest extends TestCase
{
    public function test_google_exchange_posts_authorization_code_and_maps_tokens(): void
    {
        Carbon::setTestNow('2026-05-31 10:00:00');
        Config::set('doxticket.oauth.providers.gmail.client_id', 'google-client-id');
        Config::set('doxticket.oauth.providers.gmail.client_secret', 'google-secret');
        Config::set('doxticket.oauth.providers.gmail.redirect_uri', 'https://doxticket.test/oauth/gmail/callback');
        Config::set('doxticket.oauth.providers.gmail.token_endpoint', 'https://oauth2.googleapis.com/token');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in' => 3600,
                'scope' => 'https://mail.google.com/',
            ]),
        ]);

        $tokens = app(OAuthHttpTokenClient::class)->exchange('gmail', 'code-123');

        $this->assertSame('access-token', $tokens->accessToken);
        $this->assertSame('refresh-token', $tokens->refreshToken);
        $this->assertTrue($tokens->expiresAt->equalTo(Carbon::parse('2026-05-31 11:00:00')));
        $this->assertSame(['https://mail.google.com/'], $tokens->scopes);

        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'authorization_code'
            && $request['client_id'] === 'google-client-id'
            && $request['client_secret'] === 'google-secret'
            && $request['redirect_uri'] === 'https://doxticket.test/oauth/gmail/callback'
            && $request['code'] === 'code-123');
    }

    public function test_microsoft_exchange_uses_configured_tenant_token_endpoint(): void
    {
        Config::set('doxticket.oauth.providers.microsoft365.client_id', 'microsoft-client-id');
        Config::set('doxticket.oauth.providers.microsoft365.client_secret', 'microsoft-secret');
        Config::set('doxticket.oauth.providers.microsoft365.redirect_uri', 'https://doxticket.test/oauth/microsoft/callback');
        Config::set('doxticket.oauth.providers.microsoft365.tenant', 'contoso.test');
        Config::set('doxticket.oauth.providers.microsoft365.scopes', ['offline_access']);

        Http::fake([
            'https://login.microsoftonline.com/contoso.test/oauth2/v2.0/token' => Http::response([
                'access_token' => 'graph-token',
                'expires_in' => 1800,
            ]),
        ]);

        $tokens = app(OAuthHttpTokenClient::class)->exchange('microsoft365', 'code-456');

        $this->assertSame('graph-token', $tokens->accessToken);
        $this->assertNull($tokens->refreshToken);
        $this->assertSame(['offline_access'], $tokens->scopes);

        Http::assertSent(fn ($request) => $request->url() === 'https://login.microsoftonline.com/contoso.test/oauth2/v2.0/token'
            && $request['client_id'] === 'microsoft-client-id'
            && $request['code'] === 'code-456');
    }

    public function test_refresh_posts_refresh_token_grant_and_maps_tokens(): void
    {
        Carbon::setTestNow('2026-05-31 10:00:00');
        Config::set('doxticket.oauth.providers.gmail.client_id', 'google-client-id');
        Config::set('doxticket.oauth.providers.gmail.client_secret', 'google-secret');
        Config::set('doxticket.oauth.providers.gmail.redirect_uri', 'https://doxticket.test/oauth/gmail/callback');
        Config::set('doxticket.oauth.providers.gmail.token_endpoint', 'https://oauth2.googleapis.com/token');
        Config::set('doxticket.oauth.providers.gmail.scopes', ['https://mail.google.com/']);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'refreshed-access-token',
                'expires_in' => 1800,
            ]),
        ]);

        $tokens = app(OAuthHttpTokenClient::class)->refresh('gmail', 'refresh-token-secret');

        $this->assertSame('refreshed-access-token', $tokens->accessToken);
        $this->assertNull($tokens->refreshToken);
        $this->assertTrue($tokens->expiresAt->equalTo(Carbon::parse('2026-05-31 10:30:00')));
        $this->assertSame(['https://mail.google.com/'], $tokens->scopes);

        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'refresh_token'
            && $request['client_id'] === 'google-client-id'
            && $request['client_secret'] === 'google-secret'
            && $request['refresh_token'] === 'refresh-token-secret');
    }

    public function test_exchange_throws_provider_error_body_when_token_request_fails(): void
    {
        Config::set('doxticket.oauth.providers.gmail.client_id', 'google-client-id');
        Config::set('doxticket.oauth.providers.gmail.client_secret', 'google-secret');
        Config::set('doxticket.oauth.providers.gmail.redirect_uri', 'https://doxticket.test/oauth/gmail/callback');
        Config::set('doxticket.oauth.providers.gmail.token_endpoint', 'https://oauth2.googleapis.com/token');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response('invalid_grant client_secret=google-secret', 400),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid_grant client_secret=google-secret');

        app(OAuthHttpTokenClient::class)->exchange('gmail', 'bad-code');
    }
}
