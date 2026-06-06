<?php

namespace Tests\Feature\Mail;

use App\Contracts\Mail\OAuthTokenClient;
use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\User;
use App\Services\Mail\OAuthStateStore;
use App\Support\Mail\OAuthTokenSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OAuthCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_exchanges_code_and_stores_tokens_for_active_company_account(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $account = MailAccount::factory()->for($membership->company)->create([
            'provider' => 'gmail',
            'from_email' => 'soporte@example.test',
            'last_error' => 'token vencido',
        ]);
        $state = $this->stateFor('gmail', $membership->company_id);

        $this->app->instance(OAuthTokenClient::class, new FakeOAuthTokenClient(
            new OAuthTokenSet(
                accessToken: 'new-access-token',
                refreshToken: 'new-refresh-token',
                expiresAt: Carbon::parse('2026-05-31 12:30:00'),
                scopes: ['https://mail.google.com/'],
                providerUserId: 'google-user-123',
            ),
        ));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.settings.mail.oauth.callback', [
                'provider' => 'gmail',
                'code' => 'authorization-code',
                'state' => $state,
            ]))
            ->assertRedirect('/app/settings')
            ->assertSessionHas('status', 'mail-oauth-connected');

        $account->refresh();

        $this->assertSame('new-access-token', $account->oauth_access_token);
        $this->assertSame('new-refresh-token', $account->oauth_refresh_token);
        $this->assertSame(['https://mail.google.com/'], $account->oauth_scopes);
        $this->assertSame('google-user-123', $account->oauth_provider_user_id);
        $this->assertNull($account->last_error);
    }

    public function test_callback_rejects_invalid_state_without_calling_provider(): void
    {
        [$user, $membership] = $this->tenantFixture();
        MailAccount::factory()->for($membership->company)->create([
            'provider' => 'microsoft365',
        ]);

        $this->app->instance(OAuthTokenClient::class, new FakeOAuthTokenClient(
            new OAuthTokenSet('token', 'refresh', now()->addHour(), ['Mail.ReadWrite'], null),
        ));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.settings.mail.oauth.callback', [
                'provider' => 'microsoft365',
                'code' => 'authorization-code',
                'state' => 'invalid-state',
            ]))
            ->assertRedirect('/app/settings')
            ->assertSessionHasErrors('oauth');

        $this->assertSame(0, FakeOAuthTokenClient::$calls);
    }

    public function test_callback_records_sanitized_provider_error_on_active_company_account(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $account = MailAccount::factory()->for($membership->company)->create([
            'provider' => 'gmail',
            'oauth_access_token' => 'old-token',
        ]);
        $state = $this->stateFor('gmail', $membership->company_id);

        $this->app->instance(OAuthTokenClient::class, new FakeOAuthTokenClient(
            exceptionMessage: 'invalid_grant client_secret=very-secret-token',
        ));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.settings.mail.oauth.callback', [
                'provider' => 'gmail',
                'code' => 'bad-code',
                'state' => $state,
            ]))
            ->assertRedirect('/app/settings')
            ->assertSessionHasErrors('oauth');

        $account->refresh();

        $this->assertSame('old-token', $account->oauth_access_token);
        $this->assertSame('invalid_grant client_secret=[redacted]', $account->last_error);
    }

    private function stateFor(string $provider, int $companyId): string
    {
        return app(OAuthStateStore::class)->create($provider, $companyId);
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'admin', 'status' => 'active']);

        Config::set('doxticket.oauth.providers.gmail.client_id', 'google-client-id');
        Config::set('doxticket.oauth.providers.gmail.client_secret', 'google-secret');
        Config::set('doxticket.oauth.providers.gmail.redirect_uri', 'https://doxticket.test/oauth/gmail/callback');
        Config::set('doxticket.oauth.providers.microsoft365.client_id', 'microsoft-client-id');
        Config::set('doxticket.oauth.providers.microsoft365.client_secret', 'microsoft-secret');
        Config::set('doxticket.oauth.providers.microsoft365.redirect_uri', 'https://doxticket.test/oauth/microsoft/callback');

        return [$user, $membership];
    }
}

class FakeOAuthTokenClient implements OAuthTokenClient
{
    public static int $calls = 0;

    public function __construct(
        private readonly ?OAuthTokenSet $tokens = null,
        private readonly ?string $exceptionMessage = null,
    ) {
        self::$calls = 0;
    }

    public function exchange(string $provider, string $code): OAuthTokenSet
    {
        self::$calls++;

        if ($this->exceptionMessage !== null) {
            throw new \RuntimeException($this->exceptionMessage);
        }

        return $this->tokens;
    }

    public function refresh(string $provider, string $refreshToken): OAuthTokenSet
    {
        throw new \BadMethodCallException('Refresh token grant is not used by this test.');
    }
}
