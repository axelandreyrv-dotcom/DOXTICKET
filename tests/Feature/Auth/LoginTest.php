<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_one_active_membership_logs_in_and_gets_active_company(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);
        $company = Company::factory()->create();
        $membership = Membership::factory()->for($company)->for($user)->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect('/app/tickets');
        $response->assertSessionHas('active_membership_id', $membership->id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_multiple_memberships_must_choose_company(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);
        Membership::factory()->for($user)->for(Company::factory())->create();
        Membership::factory()->for($user)->for(Company::factory())->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect('/app/companies');
        $response->assertSessionMissing('active_membership_id');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_ignores_deleted_company_memberships_when_other_companies_remain(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);
        $deletedCompany = Company::factory()->create(['status' => 'active']);
        $activeCompanyOne = Company::factory()->create(['status' => 'active']);
        $activeCompanyTwo = Company::factory()->create(['status' => 'active']);

        Membership::factory()->for($user)->for($deletedCompany)->create(['status' => 'active']);
        Membership::factory()->for($user)->for($activeCompanyOne)->create(['status' => 'active']);
        Membership::factory()->for($user)->for($activeCompanyTwo)->create(['status' => 'active']);

        $deletedCompany->delete();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect('/app/companies');
        $response->assertSessionMissing('active_membership_id');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_uses_generic_error_and_does_not_authenticate(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_get_login_redirects_to_authenticated_entry_without_home_loop(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/app/entry');

        $this->assertNotSame(url('/'), $response->headers->get('Location'));
    }

    public function test_invalid_login_message_is_shown_in_spanish(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'qa@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');

        $this->followRedirects($response)
            ->assertSee('Las credenciales no coinciden con nuestros registros.')
            ->assertDontSee('These credentials do not match our records.');
    }

    public function test_login_validation_errors_are_accessible_and_in_spanish(): void
    {
        $response = $this->followingRedirects()
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => '',
            ]);

        $response->assertOk()
            ->assertSee('id="email"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="email-error"', false)
            ->assertSee('id="email-error"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('El campo correo es obligatorio.');
    }
}
