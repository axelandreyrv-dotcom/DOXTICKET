<?php

namespace Tests\Feature\Mail;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class OAuthAuthorizationRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_start_gmail_oauth_for_active_company(): void
    {
        [$user, $membership] = $this->tenantFixture();
        Config::set('doxticket.oauth.providers.gmail.client_id', 'google-client-id');
        Config::set('doxticket.oauth.providers.gmail.redirect_uri', 'https://doxticket.test/oauth/google/callback');

        $response = $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.settings.mail.oauth.redirect', 'gmail'));

        $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
        $response->assertRedirectContains('client_id=google-client-id');
        $response->assertRedirectContains('state=');

        $this->assertNotEmpty(session('doxticket.mail_oauth_states'));
        $this->assertSame($membership->company_id, array_values(session('doxticket.mail_oauth_states'))[0]['company_id']);
    }

    public function test_oauth_start_rejects_unknown_provider(): void
    {
        [$user, $membership] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.settings.mail.oauth.redirect', 'unknown'))
            ->assertNotFound();
    }

    public function test_oauth_start_requires_provider_configuration(): void
    {
        [$user, $membership] = $this->tenantFixture();
        Config::set('doxticket.oauth.providers.gmail.client_id', null);
        Config::set('doxticket.oauth.providers.gmail.redirect_uri', null);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->from('/app/settings')
            ->post(route('app.settings.mail.oauth.redirect', 'gmail'))
            ->assertRedirect('/app/settings')
            ->assertSessionHasErrors('oauth');
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'admin', 'status' => 'active']);

        return [$user, $membership];
    }
}
