<?php

namespace Tests\Unit\Mail;

use App\Services\Mail\OAuthAuthorizationUrlFactory;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OAuthAuthorizationUrlFactoryTest extends TestCase
{
    public function test_google_authorization_url_uses_offline_access_and_configured_scopes(): void
    {
        Config::set('doxticket.oauth.providers.gmail.client_id', 'google-client-id');
        Config::set('doxticket.oauth.providers.gmail.redirect_uri', 'https://doxticket.test/oauth/google/callback');
        Config::set('doxticket.oauth.providers.gmail.scopes', ['https://mail.google.com/']);

        $url = app(OAuthAuthorizationUrlFactory::class)->make('gmail', 'state-token');
        $parts = parse_url($url);
        parse_str($parts['query'], $query);

        $this->assertSame('https', $parts['scheme']);
        $this->assertSame('accounts.google.com', $parts['host']);
        $this->assertSame('/o/oauth2/v2/auth', $parts['path']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('google-client-id', $query['client_id']);
        $this->assertSame('https://doxticket.test/oauth/google/callback', $query['redirect_uri']);
        $this->assertSame('https://mail.google.com/', $query['scope']);
        $this->assertSame('state-token', $query['state']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertSame('true', $query['include_granted_scopes']);
    }

    public function test_microsoft_authorization_url_requests_offline_access_and_graph_mail_scopes(): void
    {
        Config::set('doxticket.oauth.providers.microsoft365.client_id', 'microsoft-client-id');
        Config::set('doxticket.oauth.providers.microsoft365.redirect_uri', 'https://doxticket.test/oauth/microsoft/callback');
        Config::set('doxticket.oauth.providers.microsoft365.tenant', 'organizations');
        Config::set('doxticket.oauth.providers.microsoft365.scopes', [
            'offline_access',
            'https://graph.microsoft.com/Mail.ReadWrite',
            'https://graph.microsoft.com/Mail.Send',
        ]);

        $url = app(OAuthAuthorizationUrlFactory::class)->make('microsoft365', 'state-token');
        $parts = parse_url($url);
        parse_str($parts['query'], $query);

        $this->assertSame('https', $parts['scheme']);
        $this->assertSame('login.microsoftonline.com', $parts['host']);
        $this->assertSame('/organizations/oauth2/v2.0/authorize', $parts['path']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('microsoft-client-id', $query['client_id']);
        $this->assertSame('https://doxticket.test/oauth/microsoft/callback', $query['redirect_uri']);
        $this->assertSame('offline_access https://graph.microsoft.com/Mail.ReadWrite https://graph.microsoft.com/Mail.Send', $query['scope']);
        $this->assertSame('state-token', $query['state']);
        $this->assertSame('query', $query['response_mode']);
    }
}
