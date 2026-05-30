<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_list_links_to_a_tenant_scoped_detail_page(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['subject' => 'VPN oficina principal']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get('/app/tickets')
            ->assertOk()
            ->assertSee(route('app.tickets.show', $ticket->public_key), false);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('VPN oficina principal')
            ->assertSee($ticket->public_key)
            ->assertSee('Agregar Nota Interna');
    }

    public function test_ticket_detail_can_be_opened_by_public_key(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['subject' => 'Ticket por clave visible']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee('Ticket por clave visible');
    }

    public function test_ticket_detail_is_not_available_across_companies(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $otherCompany = Company::factory()->create();
        $otherTicket = Ticket::factory()->for($otherCompany)->create(['subject' => 'No visible']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $otherTicket))
            ->assertNotFound();
    }

    public function test_agent_can_add_internal_note_without_trusting_company_input(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $otherCompany = Company::factory()->create();
        $ticket = Ticket::factory()->for($activeMembership->company)->create();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->post(route('app.tickets.messages.store', $ticket), [
                'company_id' => $otherCompany->id,
                'body_text' => 'Se reviso conectividad y queda evidencia interna.',
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $this->assertDatabaseHas('ticket_messages', [
            'company_id' => $activeMembership->company_id,
            'ticket_id' => $ticket->id,
            'author_membership_id' => $activeMembership->id,
            'visibility' => 'internal',
            'direction' => 'internal',
            'body_text' => 'Se reviso conectividad y queda evidencia interna.',
        ]);
    }

    public function test_agent_can_resolve_then_close_a_ticket(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->patch(route('app.tickets.status.update', $ticket), ['status' => 'resolved'])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->patch(route('app.tickets.status.update', $ticket), ['status' => 'closed'])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $ticket->refresh();
        $this->assertSame('closed', $ticket->status);
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_ticket_cannot_be_closed_before_it_is_resolved(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->from(route('app.tickets.show', $ticket))
            ->patch(route('app.tickets.status.update', $ticket), ['status' => 'closed'])
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
