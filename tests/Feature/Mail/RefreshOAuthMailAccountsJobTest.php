<?php

namespace Tests\Feature\Mail;

use App\Jobs\Mail\RefreshOAuthMailAccountsJob;
use App\Models\Company;
use App\Models\MailAccount;
use App\Services\Mail\OAuthTokenRefresher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefreshOAuthMailAccountsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_refreshes_only_active_oauth_mail_accounts(): void
    {
        $gmail = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'gmail',
            'is_active' => true,
        ]);
        $microsoft = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'microsoft365',
            'is_active' => true,
        ]);
        MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'imap_smtp',
            'is_active' => true,
        ]);
        MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'gmail',
            'is_active' => false,
        ]);
        $refresher = new RecordingOAuthTokenRefresher;
        $this->app->instance(OAuthTokenRefresher::class, $refresher);

        RefreshOAuthMailAccountsJob::dispatchSync();

        $this->assertSame([$gmail->id, $microsoft->id], $refresher->mailAccountIds);
    }
}

class RecordingOAuthTokenRefresher extends OAuthTokenRefresher
{
    /** @var list<int> */
    public array $mailAccountIds = [];

    public function __construct() {}

    public function refreshIfNeeded(MailAccount $account): bool
    {
        $this->mailAccountIds[] = $account->id;

        return true;
    }
}
