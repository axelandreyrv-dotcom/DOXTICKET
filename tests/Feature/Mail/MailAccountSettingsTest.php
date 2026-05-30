<?php

namespace Tests\Feature\Mail;

use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
