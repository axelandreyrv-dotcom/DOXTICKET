<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_authenticated_entry_redirects_to_admin_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/app/entry')
            ->assertRedirect('/admin');
    }

    public function test_user_with_active_company_context_redirects_to_tickets(): void
    {
        [$user, $membership] = $this->userWithMembership();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get('/app/entry')
            ->assertRedirect('/app/tickets');
    }

    public function test_user_with_one_company_gets_context_and_redirects_to_tickets(): void
    {
        [$user, $membership] = $this->userWithMembership();

        $this->actingAs($user)
            ->get('/app/entry')
            ->assertRedirect('/app/tickets')
            ->assertSessionHas('active_membership_id', $membership->id);
    }

    public function test_user_who_must_select_company_redirects_to_company_selector(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Membership::factory()->for($user)->for(Company::factory())->create(['status' => 'active']);
        Membership::factory()->for($user)->for(Company::factory())->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/app/entry')
            ->assertRedirect('/app/companies')
            ->assertSessionMissing('active_membership_id');
    }

    /**
     * @return array{User, Membership}
     */
    private function userWithMembership(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $membership = Membership::factory()->for($user)->for(Company::factory())->create([
            'status' => 'active',
        ]);

        return [$user, $membership];
    }
}
