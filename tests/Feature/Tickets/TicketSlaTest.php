<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSlaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_gets_a_resolution_sla_due_date_from_priority_defaults(): void
    {
        $company = Company::factory()->create();

        $ticket = Ticket::factory()->for($company)->create([
            'priority' => 'urgent',
            'created_at' => now(),
        ]);

        $this->assertNotNull($ticket->sla_due_at);
        $this->assertTrue($ticket->sla_due_at->isSameMinute(now()->addHours(8)));
    }

    public function test_ticket_workspace_shows_only_active_overdue_tickets_from_selected_company(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $otherCompany = Company::factory()->create();

        Ticket::factory()->for($activeMembership->company)->create([
            'status' => 'open',
            'sla_due_at' => now()->subMinute(),
        ]);

        Ticket::factory()->for($activeMembership->company)->create([
            'status' => 'resolved',
            'sla_due_at' => now()->subMinute(),
        ]);

        Ticket::factory()->for($otherCompany)->create([
            'status' => 'open',
            'sla_due_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['sla' => 'overdue']))
            ->assertOk()
            ->assertSee('SLA vencido');
    }

    public function test_ticket_list_can_filter_overdue_tickets(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Servidor caido',
            'status' => 'open',
            'sla_due_at' => now()->subMinute(),
        ]);

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Solicitud normal',
            'status' => 'open',
            'sla_due_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['sla' => 'overdue']))
            ->assertOk()
            ->assertSee('Servidor caido')
            ->assertDontSee('Solicitud normal')
            ->assertSee('SLA vencido');
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'agent', 'status' => 'active']);

        return [$user, $membership];
    }
}
