<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPropertiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_update_ticket_properties_for_the_active_company(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $assignee = Membership::factory()
            ->for(User::factory()->create(['name' => 'Soporte Omu']))
            ->for($activeMembership->company)
            ->create(['role' => 'agent', 'status' => 'active']);
        $ticket = Ticket::factory()->for($activeMembership->company)->create([
            'status' => 'open',
            'priority' => 'medium',
            'ticket_type' => 'question',
            'assigned_to_membership_id' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->patch(route('app.tickets.properties.update', $ticket->public_key), [
                'status' => 'pending',
                'priority' => 'urgent',
                'ticket_type' => 'incident',
                'assigned_to_membership_id' => $assignee->id,
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $ticket->refresh();
        $this->assertSame('pending', $ticket->status);
        $this->assertSame('urgent', $ticket->priority);
        $this->assertSame('incident', $ticket->ticket_type);
        $this->assertSame($assignee->id, $ticket->assigned_to_membership_id);

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $activeMembership->company_id,
            'ticket_id' => $ticket->id,
            'actor_membership_id' => $activeMembership->id,
            'type' => 'ticket.assigned',
        ]);
    }

    public function test_agent_cannot_assign_ticket_to_membership_from_another_company(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $otherCompany = Company::factory()->create();
        $otherMembership = Membership::factory()->for(User::factory())->for($otherCompany)->create(['status' => 'active']);
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->from(route('app.tickets.show', $ticket->public_key))
            ->patch(route('app.tickets.properties.update', $ticket->public_key), [
                'status' => 'open',
                'priority' => 'medium',
                'ticket_type' => 'request',
                'assigned_to_membership_id' => $otherMembership->id,
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHasErrors('assigned_to_membership_id');

        $this->assertNull($ticket->fresh()->assigned_to_membership_id);
    }

    public function test_closed_status_still_requires_resolved_first(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->from(route('app.tickets.show', $ticket->public_key))
            ->patch(route('app.tickets.properties.update', $ticket->public_key), [
                'status' => 'closed',
                'priority' => 'medium',
                'ticket_type' => 'request',
                'assigned_to_membership_id' => null,
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHasErrors('status');

        $this->assertSame('open', $ticket->fresh()->status);
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
