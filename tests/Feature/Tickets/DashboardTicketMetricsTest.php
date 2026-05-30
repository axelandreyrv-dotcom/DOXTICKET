<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTicketMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_only_the_active_company_tickets(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $otherCompany = Company::factory()->create();
        $membership = Membership::factory()->for($user)->for($company)->create(['status' => 'active']);

        Ticket::factory()->for($company)->create(['status' => 'new']);
        Ticket::factory()->for($company)->create(['status' => 'open']);
        Ticket::factory()->for($company)->create(['status' => 'in_progress', 'assigned_to_membership_id' => $membership->id]);
        Ticket::factory()->for($company)->create(['status' => 'resolved']);
        Ticket::factory()->for($otherCompany)->create(['status' => 'new']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get('/app/dashboard')
            ->assertOk()
            ->assertSee('Dox IT')
            ->assertSeeInOrder(['Nuevos', '1'])
            ->assertSeeInOrder(['Activos', '2'])
            ->assertSeeInOrder(['Asignados', '1'])
            ->assertSeeInOrder(['Resueltos', '1']);
    }
}
