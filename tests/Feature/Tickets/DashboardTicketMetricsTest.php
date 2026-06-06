<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTicketMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_legacy_route_redirects_to_ticket_workspace(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['status' => 'active']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get('/app/dashboard')
            ->assertRedirect('/app/tickets');
    }
}
