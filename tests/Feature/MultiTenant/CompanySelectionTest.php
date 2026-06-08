<?php

namespace Tests\Feature\MultiTenant;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_select_their_active_company_membership(): void
    {
        $user = User::factory()->create();
        $membership = Membership::factory()
            ->for($user)
            ->for(Company::factory())
            ->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/app/companies/select', [
            'membership_id' => $membership->id,
        ]);

        $response->assertRedirect('/app/tickets');
        $response->assertSessionHas('active_membership_id', $membership->id);
    }

    public function test_authenticated_user_cannot_select_another_users_membership(): void
    {
        $user = User::factory()->create();
        $otherMembership = Membership::factory()
            ->for(User::factory())
            ->for(Company::factory())
            ->create(['status' => 'active']);

        $this->actingAs($user)
            ->post('/app/companies/select', ['membership_id' => $otherMembership->id])
            ->assertForbidden();
    }

    public function test_app_dashboard_legacy_route_requires_active_membership_context(): void
    {
        $user = User::factory()->create();
        Membership::factory()->for($user)->for(Company::factory())->create(['status' => 'active']);
        Membership::factory()->for($user)->for(Company::factory())->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/app/dashboard')
            ->assertRedirect('/app/companies');
    }

    public function test_app_dashboard_legacy_route_redirects_to_tickets(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT QA']);
        Membership::factory()->for($user)->for($company)->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/app/dashboard')
            ->assertRedirect('/app/tickets');
    }

    public function test_user_without_active_companies_is_redirected_to_company_selector_instead_of_forbidden(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        Membership::factory()->for($user)->for($company)->create(['status' => 'active']);
        $company->delete();

        $this->actingAs($user)
            ->get('/app/tickets')
            ->assertRedirect('/app/companies')
            ->assertSessionHas('status', 'No tienes empresas activas. Solicita acceso o crea una empresa desde el panel admin.');

        $this->actingAs($user)
            ->get('/app/companies')
            ->assertOk()
            ->assertSee('No tienes empresas activas.');
    }
}
