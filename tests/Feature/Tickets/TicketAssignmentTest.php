<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_assign_ticket_to_the_active_membership(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $otherMembership = Membership::factory()->for(User::factory())->for($activeMembership->company)->create(['role' => 'agent']);
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['assigned_to_membership_id' => null]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->post(route('app.tickets.assign-self', $ticket->public_key), [
                'assigned_to_membership_id' => $otherMembership->id,
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $ticket->refresh();
        $this->assertSame($activeMembership->id, $ticket->assigned_to_membership_id);
        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $activeMembership->company_id,
            'ticket_id' => $ticket->id,
            'actor_membership_id' => $activeMembership->id,
            'type' => 'ticket.assigned_self',
        ]);
    }

    public function test_agent_can_take_ticket_assigned_to_another_agent_in_same_company(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $previousMembership = Membership::factory()->for(User::factory())->for($activeMembership->company)->create(['role' => 'agent']);
        $ticket = Ticket::factory()->for($activeMembership->company)->create([
            'assigned_to_membership_id' => $previousMembership->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->post(route('app.tickets.assign-self', $ticket->public_key))
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $ticket->refresh();
        $this->assertSame($activeMembership->id, $ticket->assigned_to_membership_id);
    }

    public function test_agent_cannot_assign_ticket_from_another_company(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $otherCompany = Company::factory()->create();
        $ticket = Ticket::factory()->for($otherCompany)->create();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->post(route('app.tickets.assign-self', $ticket->public_key))
            ->assertNotFound();
    }

    public function test_ticket_list_shows_assign_self_and_detail_shows_agent_property_for_unassigned_ticket(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create([
            'assigned_to_membership_id' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee(route('app.tickets.assign-self', $ticket->public_key), false)
            ->assertSee('Asignarme');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee(route('app.tickets.properties.update', $ticket->public_key), false)
            ->assertSee('Agente')
            ->assertSee('Sin asignar');
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
