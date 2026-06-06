<?php

namespace Tests\Feature\Mail;

use App\Contracts\Mail\OAuthTokenClient;
use App\Models\Company;
use App\Models\MailAccount;
use App\Services\Mail\OAuthTokenRefresher;
use App\Support\Mail\OAuthTokenSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OAuthTokenRefresherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refreshes_expiring_oauth_account_and_preserves_missing_refresh_token(): void
    {
        Carbon::setTestNow('2026-05-31 10:00:00');
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'gmail',
            'oauth_access_token' => 'old-access-token',
            'oauth_refresh_token' => 'existing-refresh-token',
            'oauth_expires_at' => now()->addMinutes(3),
            'oauth_scopes' => ['https://mail.google.com/'],
            'last_error' => 'expired',
        ]);
        $client = new FakeRefreshTokenClient(new OAuthTokenSet(
            accessToken: 'new-access-token',
            refreshToken: null,
            expiresAt: now()->addHour(),
            scopes: ['https://mail.google.com/'],
            providerUserId: 'google-user-123',
        ));
        $this->app->instance(OAuthTokenClient::class, $client);

        $refreshed = app(OAuthTokenRefresher::class)->refreshIfNeeded($account);

        $this->assertTrue($refreshed);
        $this->assertSame([['gmail', 'existing-refresh-token']], $client->refreshCalls);

        $account->refresh();
        $this->assertSame('new-access-token', $account->oauth_access_token);
        $this->assertSame('existing-refresh-token', $account->oauth_refresh_token);
        $this->assertNull($account->last_error);
    }

    public function test_it_skips_oauth_account_when_token_is_still_fresh(): void
    {
        Carbon::setTestNow('2026-05-31 10:00:00');
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'microsoft365',
            'oauth_access_token' => 'fresh-access-token',
            'oauth_refresh_token' => 'refresh-token',
            'oauth_expires_at' => now()->addMinutes(20),
        ]);
        $client = new FakeRefreshTokenClient(new OAuthTokenSet(
            accessToken: 'new-access-token',
            refreshToken: 'new-refresh-token',
            expiresAt: now()->addHour(),
            scopes: ['offline_access'],
            providerUserId: null,
        ));
        $this->app->instance(OAuthTokenClient::class, $client);

        $refreshed = app(OAuthTokenRefresher::class)->refreshIfNeeded($account);

        $this->assertFalse($refreshed);
        $this->assertSame([], $client->refreshCalls);
        $this->assertSame('fresh-access-token', $account->refresh()->oauth_access_token);
    }

    public function test_it_records_sanitized_error_without_replacing_existing_token(): void
    {
        Carbon::setTestNow('2026-05-31 10:00:00');
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'gmail',
            'oauth_access_token' => 'old-access-token',
            'oauth_refresh_token' => 'secret-refresh-token',
            'oauth_expires_at' => now()->subMinute(),
        ]);
        $client = new FakeRefreshTokenClient(
            exceptionMessage: 'invalid_grant refresh_token=secret-refresh-token',
        );
        $this->app->instance(OAuthTokenClient::class, $client);

        $refreshed = app(OAuthTokenRefresher::class)->refreshIfNeeded($account);

        $this->assertFalse($refreshed);

        $account->refresh();
        $this->assertSame('old-access-token', $account->oauth_access_token);
        $this->assertSame('secret-refresh-token', $account->oauth_refresh_token);
        $this->assertSame('invalid_grant refresh_token=[redacted]', $account->last_error);
    }
}

class FakeRefreshTokenClient implements OAuthTokenClient
{
    /** @var list<array{string, string}> */
    public array $refreshCalls = [];

    public function __construct(
        private readonly ?OAuthTokenSet $tokens = null,
        private readonly ?string $exceptionMessage = null,
    ) {}

    public function exchange(string $provider, string $code): OAuthTokenSet
    {
        throw new \BadMethodCallException('Authorization code exchange is not used by this test.');
    }

    public function refresh(string $provider, string $refreshToken): OAuthTokenSet
    {
        $this->refreshCalls[] = [$provider, $refreshToken];

        if ($this->exceptionMessage !== null) {
            throw new \RuntimeException($this->exceptionMessage);
        }

        return $this->tokens;
    }
}
