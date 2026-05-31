<?php

namespace Tests\Feature\Activity;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_panel_lists_recent_ticket_events_for_the_active_company(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($membership->company)->create(['subject' => 'VPN oficina principal']);

        TicketEvent::withoutTenant()->create([
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'actor_user_id' => $user->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.status_changed',
            'payload' => ['from' => 'open', 'to' => 'resolved'],
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.activity.index'))
            ->assertOk()
            ->assertSee('Actividad')
            ->assertSeeText('QA Admin')
            ->assertSeeText('actualizo el estado de')
            ->assertSee('VPN oficina principal')
            ->assertSee($ticket->public_key)
            ->assertSee(route('app.tickets.show', $ticket->public_key), false);
    }

    public function test_activity_panel_does_not_show_events_from_other_companies(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $otherCompany = Company::factory()->create();
        $otherTicket = Ticket::factory()->for($otherCompany)->create(['subject' => 'Ticket externo']);

        TicketEvent::withoutTenant()->create([
            'company_id' => $otherCompany->id,
            'ticket_id' => $otherTicket->id,
            'type' => 'ticket.created_manual',
            'payload' => ['source' => 'manual'],
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.activity.index'))
            ->assertOk()
            ->assertDontSee('Ticket externo');
    }

    public function test_activity_panel_filters_by_activity_type(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $createdTicket = Ticket::factory()->for($membership->company)->create(['subject' => 'Alta de monitor']);
        $assignedTicket = Ticket::factory()->for($membership->company)->create(['subject' => 'Laptop sin red']);

        TicketEvent::withoutTenant()->create([
            'company_id' => $membership->company_id,
            'ticket_id' => $createdTicket->id,
            'actor_user_id' => $user->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.created_manual',
            'payload' => ['source' => 'manual'],
        ]);

        TicketEvent::withoutTenant()->create([
            'company_id' => $membership->company_id,
            'ticket_id' => $assignedTicket->id,
            'actor_user_id' => $user->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.assigned_self',
            'payload' => ['to_membership_id' => $membership->id],
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.activity.index', ['type' => 'assignment']))
            ->assertOk()
            ->assertSee('Laptop sin red')
            ->assertSee('se asigno')
            ->assertDontSee('Alta de monitor');
    }

    public function test_app_navigation_links_to_activity_panel(): void
    {
        [$user, $membership] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertSee(route('app.activity.index'), false)
            ->assertSee('Actividad');
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create([
            'name' => 'QA Admin',
            'email' => 'qa@example.test',
        ]);
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'agent', 'status' => 'active']);

        return [$user, $membership];
    }
}
