<?php

namespace Tests\Feature\Mail;

use App\Contracts\Mail\MailAccountTester;
use App\Jobs\Mail\IngestMailboxJob;
use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\User;
use App\Support\Mail\MailAccountTestResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MailAccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_mail_account_is_saved_for_the_active_company_with_encrypted_secret(): void
    {
        [$user, $activeCompany, $otherCompany] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($activeCompany)->value('id');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->post('/app/settings/mail', [
                'company_id' => $otherCompany->id,
                'provider' => 'imap_smtp',
                'from_name' => 'Soporte TI',
                'from_email' => 'soporte@example.test',
                'host_imap' => 'imap.example.test',
                'port_imap' => 993,
                'security_imap' => 'ssl',
                'host_smtp' => 'smtp.example.test',
                'port_smtp' => 587,
                'security_smtp' => 'tls',
                'username' => 'soporte@example.test',
                'password' => 'super-secret-mail-password',
                'folder_in' => 'INBOX',
                'auto_reply_enabled' => '1',
            ])
            ->assertRedirect('/app/settings');

        $account = MailAccount::withoutTenant()->where('from_email', 'soporte@example.test')->firstOrFail();
        $rawSecret = DB::table('mail_accounts')->whereKey($account->id)->value('password_encrypted');

        $this->assertSame($activeCompany->id, $account->company_id);
        $this->assertNotSame($otherCompany->id, $account->company_id);
        $this->assertNotSame('super-secret-mail-password', $rawSecret);
        $this->assertSame('super-secret-mail-password', $account->password_encrypted);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->get('/app/settings')
            ->assertOk()
            ->assertSee('soporte@example.test')
            ->assertSee('imap.example.test')
            ->assertDontSee('super-secret-mail-password');
    }

    public function test_saving_mail_settings_updates_existing_company_account_without_erasing_password(): void
    {
        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');

        MailAccount::factory()->for($company)->create([
            'from_email' => 'old-support@example.test',
            'password_encrypted' => 'original-secret',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->post('/app/settings/mail', [
                'provider' => 'imap_smtp',
                'from_name' => 'Mesa de Ayuda',
                'from_email' => 'new-support@example.test',
                'host_imap' => 'imap.new.example.test',
                'port_imap' => 993,
                'security_imap' => 'ssl',
                'host_smtp' => 'smtp.new.example.test',
                'port_smtp' => 587,
                'security_smtp' => 'tls',
                'username' => 'new-support@example.test',
                'folder_in' => 'INBOX',
            ])
            ->assertRedirect('/app/settings');

        $accounts = MailAccount::withoutTenant()->whereBelongsTo($company)->get();

        $this->assertCount(1, $accounts);
        $this->assertSame('new-support@example.test', $accounts->first()->from_email);
        $this->assertSame('original-secret', $accounts->first()->password_encrypted);
    }

    public function test_first_mail_account_requires_password_with_spanish_message(): void
    {
        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->from('/app/settings')
            ->post('/app/settings/mail', [
                'provider' => 'imap_smtp',
                'from_email' => 'soporte@example.test',
                'host_imap' => 'imap.example.test',
                'port_imap' => 993,
                'security_imap' => 'ssl',
                'host_smtp' => 'smtp.example.test',
                'port_smtp' => 587,
                'security_smtp' => 'tls',
                'folder_in' => 'INBOX',
            ])
            ->assertRedirect('/app/settings')
            ->assertSessionHasErrors([
                'password' => 'La contraseña es obligatoria para la primera configuración.',
            ]);
    }

    public function test_mail_settings_validation_errors_are_accessible_and_in_spanish(): void
    {
        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');

        $response = $this->followingRedirects()
            ->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->from('/app/settings')
            ->post('/app/settings/mail', [
                'provider' => 'imap_smtp',
                'from_email' => '',
                'host_imap' => '',
                'port_imap' => '',
                'security_imap' => 'ssl',
                'host_smtp' => '',
                'port_smtp' => '',
                'security_smtp' => 'tls',
                'folder_in' => '',
            ]);

        $response->assertOk()
            ->assertSee('id="from_email"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="from_email-error"', false)
            ->assertSee('id="from_email-error"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('El campo correo de soporte es obligatorio.');
    }

    public function test_mail_settings_form_uses_explicit_browser_metadata_for_technical_fields(): void
    {
        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->get('/app/settings')
            ->assertOk()
            ->assertSee('id="from_name" name="from_name" autocomplete="off"', false)
            ->assertSee('id="from_email" type="email" name="from_email" inputmode="email" autocomplete="off" spellcheck="false"', false)
            ->assertSee('id="host_imap" name="host_imap" inputmode="url" autocomplete="off" spellcheck="false"', false)
            ->assertSee('id="port_imap" type="number" name="port_imap" inputmode="numeric"', false)
            ->assertSee('id="host_smtp" name="host_smtp" inputmode="url" autocomplete="off" spellcheck="false"', false)
            ->assertSee('id="port_smtp" type="number" name="port_smtp" inputmode="numeric"', false)
            ->assertSee('id="username" name="username" autocomplete="off" spellcheck="false"', false)
            ->assertSee('id="password" type="password" name="password" autocomplete="new-password"', false)
            ->assertSee('id="folder_in" name="folder_in" autocomplete="off" spellcheck="false"', false);
    }

    public function test_mail_account_connection_test_clears_error_for_active_company_account(): void
    {
        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');
        $account = MailAccount::factory()->for($company)->create([
            'last_error' => 'Error anterior',
        ]);

        $this->app->instance(MailAccountTester::class, new FakeMailAccountTester(
            MailAccountTestResult::ok(),
        ));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->post(route('app.settings.mail.test'))
            ->assertRedirect('/app/settings')
            ->assertSessionHas('status', 'mail-test-ok');

        $this->assertNull($account->refresh()->last_error);
    }

    public function test_mail_settings_show_connection_test_sync_actions_and_ingestion_status(): void
    {
        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');

        MailAccount::factory()->for($company)->create([
            'from_email' => 'soporte@example.test',
            'last_sync_at' => now()->subMinutes(5),
            'last_uid' => '42',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->get('/app/settings')
            ->assertOk()
            ->assertSee('Probar conexión')
            ->assertSee('Revisar correo ahora')
            ->assertSee('Última sincronización')
            ->assertSee('UID 42');
    }

    public function test_manual_mail_sync_dispatches_ingestion_for_active_company_account(): void
    {
        Bus::fake();

        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');
        $account = MailAccount::factory()->for($company)->create();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->post(route('app.settings.mail.sync'))
            ->assertRedirect('/app/settings')
            ->assertSessionHas('status', 'mail-sync-completed');

        Bus::assertDispatchedSync(IngestMailboxJob::class, fn (IngestMailboxJob $job): bool => $job->mailAccountId === $account->id);
    }

    public function test_mail_account_connection_test_records_sanitized_error_for_active_company_account(): void
    {
        [$user, $company] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($company)->value('id');
        $account = MailAccount::factory()->for($company)->create();

        $this->app->instance(MailAccountTester::class, new FakeMailAccountTester(
            MailAccountTestResult::failed('Authentication failed for secret password=abc123'),
        ));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->post(route('app.settings.mail.test'))
            ->assertRedirect('/app/settings')
            ->assertSessionHasErrors(['mail_test']);

        $this->assertSame('Authentication failed for secret password=[redacted]', $account->refresh()->last_error);
    }

    public function test_mail_account_connection_test_does_not_use_other_company_account(): void
    {
        [$user, $activeCompany, $otherCompany] = $this->tenantFixture();
        $membershipId = $user->memberships()->whereBelongsTo($activeCompany)->value('id');
        MailAccount::factory()->for($otherCompany)->create();

        $this->app->instance(MailAccountTester::class, new FakeMailAccountTester(
            MailAccountTestResult::ok(),
        ));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membershipId])
            ->post(route('app.settings.mail.test'))
            ->assertRedirect('/app/settings')
            ->assertSessionHasErrors(['mail_account']);
    }

    /**
     * @return array{User, Company, Company}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create();
        $activeCompany = Company::factory()->create(['name' => 'Dox IT']);
        $otherCompany = Company::factory()->create(['name' => 'Otra Empresa']);

        Membership::factory()->for($user)->for($activeCompany)->create(['role' => 'admin', 'status' => 'active']);
        Membership::factory()->for(User::factory())->for($otherCompany)->create(['role' => 'admin', 'status' => 'active']);

        return [$user, $activeCompany, $otherCompany];
    }
}

class FakeMailAccountTester implements MailAccountTester
{
    public function __construct(private readonly MailAccountTestResult $result) {}

    public function test(MailAccount $account): MailAccountTestResult
    {
        return $this->result;
    }
}
