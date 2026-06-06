<?php

namespace Tests\Feature\Mail;

use App\Models\Company;
use App\Models\MailAccount;
use App\Services\Mail\OAuthTokenStore;
use App\Support\Mail\OAuthTokenSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class OAuthTokenStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_tokens_are_saved_encrypted_for_supported_provider(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'gmail',
            'from_email' => 'soporte@example.test',
            'last_error' => 'token vencido',
            'password_encrypted' => 'old-password',
        ]);

        app(OAuthTokenStore::class)->store($account, new OAuthTokenSet(
            accessToken: 'access-token-secret',
            refreshToken: 'refresh-token-secret',
            expiresAt: now()->addHour(),
            scopes: ['https://mail.google.com/'],
            providerUserId: 'google-user-123',
        ));

        $account->refresh();
        $raw = DB::table('mail_accounts')->where('id', $account->id)->first();

        $this->assertSame('access-token-secret', $account->oauth_access_token);
        $this->assertSame('refresh-token-secret', $account->oauth_refresh_token);
        $this->assertNotSame('access-token-secret', $raw->oauth_access_token);
        $this->assertNotSame('refresh-token-secret', $raw->oauth_refresh_token);
        $this->assertSame(['https://mail.google.com/'], $account->oauth_scopes);
        $this->assertSame('google-user-123', $account->oauth_provider_user_id);
        $this->assertNull($account->last_error);
        $this->assertNull($account->password_encrypted);
        $this->assertNotNull($account->oauth_connected_at);
    }

    public function test_oauth_store_preserves_existing_refresh_token_when_new_one_is_missing(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'microsoft365',
            'oauth_refresh_token' => 'existing-refresh-token',
        ]);

        app(OAuthTokenStore::class)->store($account, new OAuthTokenSet(
            accessToken: 'new-access-token',
            refreshToken: null,
            expiresAt: now()->addMinutes(30),
            scopes: ['Mail.ReadWrite', 'Mail.Send'],
            providerUserId: 'm365-user-123',
        ));

        $account->refresh();

        $this->assertSame('new-access-token', $account->oauth_access_token);
        $this->assertSame('existing-refresh-token', $account->oauth_refresh_token);
        $this->assertSame(['Mail.ReadWrite', 'Mail.Send'], $account->oauth_scopes);
    }

    public function test_oauth_store_rejects_password_based_mail_accounts(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'imap_smtp',
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(OAuthTokenStore::class)->store($account, new OAuthTokenSet(
            accessToken: 'access-token-secret',
            refreshToken: 'refresh-token-secret',
            expiresAt: now()->addHour(),
            scopes: ['Mail.Read'],
            providerUserId: 'user-123',
        ));
    }
}
