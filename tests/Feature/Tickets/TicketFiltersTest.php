<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_list_can_filter_by_agent_priority_type_and_source(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $otherAgent = Membership::factory()
            ->for(User::factory()->create(['name' => 'Axel Ruiz']))
            ->for($activeMembership->company)
            ->create(['role' => 'agent', 'status' => 'active']);

        $matchingTicket = Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Laptop sin red',
            'status' => 'open',
            'priority' => 'urgent',
            'ticket_type' => 'incident',
            'source' => 'email',
            'assigned_to_membership_id' => $otherAgent->id,
        ]);

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Solicitud de mouse',
            'status' => 'open',
            'priority' => 'low',
            'ticket_type' => 'request',
            'source' => 'manual',
            'assigned_to_membership_id' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', [
                'agent' => (string) $otherAgent->id,
                'priority' => 'urgent',
                'type' => 'incident',
                'source' => 'email',
            ]))
            ->assertOk()
            ->assertSee($matchingTicket->subject)
            ->assertDontSee('Solicitud de mouse');
    }

    public function test_ticket_list_can_filter_unassigned_and_my_tickets(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Sin agente',
            'status' => 'open',
            'assigned_to_membership_id' => null,
        ]);

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Mi ticket',
            'status' => 'open',
            'assigned_to_membership_id' => $activeMembership->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['agent' => 'unassigned']))
            ->assertOk()
            ->assertSee('Sin agente')
            ->assertDontSee('Mi ticket');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['agent' => 'me']))
            ->assertOk()
            ->assertSee('Mi ticket')
            ->assertDontSee('Sin agente');
    }

    public function test_ticket_list_can_filter_by_specific_status(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Entrada nueva',
            'status' => 'new',
        ]);

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Entrada abierta',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['status' => 'new']))
            ->assertOk()
            ->assertSee('Entrada nueva')
            ->assertDontSee('Entrada abierta');
    }

    public function test_ticket_list_can_search_by_key_subject_or_requester_email(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        $keyMatch = Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Monitor no enciende',
            'status' => 'open',
            'public_number' => 321,
            'public_key' => 'DT-321',
            'requester_email' => 'monitor@example.test',
        ]);

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'VPN lenta',
            'status' => 'open',
            'requester_email' => 'vpn@example.test',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['q' => 'DT-321']))
            ->assertOk()
            ->assertSee($keyMatch->subject)
            ->assertDontSee('VPN lenta');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['q' => 'monitor no']))
            ->assertOk()
            ->assertSee($keyMatch->public_key)
            ->assertDontSee('VPN lenta');

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['q' => 'monitor@example.test']))
            ->assertOk()
            ->assertSee($keyMatch->subject)
            ->assertDontSee('VPN lenta');
    }

    public function test_ticket_list_search_input_uses_explicit_browser_metadata(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index', ['q' => 'DT-321']))
            ->assertOk()
            ->assertSee('type="search" name="q" value="DT-321" placeholder="Clave, asunto o correo…"', false)
            ->assertSee('autocomplete="off"', false)
            ->assertSee('spellcheck="false"', false);
    }

    public function test_ticket_list_keeps_only_search_visible_as_primary_filter(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('Buscar')
            ->assertSee('Filtrar')
            ->assertDontSee('for="agent"', false)
            ->assertDontSee('for="priority"', false)
            ->assertDontSee('for="source"', false)
            ->assertDontSee('for="sla"', false);
    }

    public function test_ticket_list_defaults_to_active_statuses(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Entrada activa',
            'status' => 'pending',
        ]);

        Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Entrada cerrada',
            'status' => 'closed',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('Entrada activa')
            ->assertDontSee('Entrada cerrada');
    }

    public function test_ticket_list_uses_simplified_status_labels(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();

        Ticket::factory()->for($activeMembership->company)->create(['status' => 'pending', 'subject' => 'Esperando dato']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('Pendiente')
            ->assertDontSee('Espera interna')
            ->assertDontSee('En progreso');
    }

    public function test_ticket_list_exposes_copy_action_for_visible_ticket_key(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create([
            'subject' => 'Clave desde lista',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('data-copy-text="'.$ticket->public_key.'"', false)
            ->assertSee('aria-label="Copiar clave '.$ticket->public_key.'"', false)
            ->assertSee('aria-describedby="ticket-list-copy-status"', false)
            ->assertSee('id="ticket-list-copy-status"', false)
            ->assertSee('role="status"', false)
            ->assertSee('aria-live="polite"', false);
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
