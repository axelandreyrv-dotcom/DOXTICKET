<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_merge_ticket_into_another_ticket_from_the_active_company(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $primary = Ticket::factory()->for($membership->company)->create(['subject' => 'VPN principal', 'status' => 'open']);
        $secondary = Ticket::factory()->for($membership->company)->create(['subject' => 'VPN duplicado', 'status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.merge.store', $secondary->public_key), [
                'target_ticket_key' => $primary->public_key,
            ])
            ->assertRedirect(route('app.tickets.show', $primary->public_key));

        $secondary->refresh();

        $this->assertTrue($secondary->merged);
        $this->assertSame('merged', $secondary->status);
        $this->assertSame($primary->id, $secondary->merged_into_ticket_id);
        $this->assertSame($membership->id, $secondary->merged_by_membership_id);
        $this->assertNotNull($secondary->merged_at);

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $membership->company_id,
            'ticket_id' => $secondary->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.merged',
        ]);

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $membership->company_id,
            'ticket_id' => $primary->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.merge_received',
        ]);
    }

    public function test_agent_cannot_merge_ticket_into_ticket_from_another_company(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $secondary = Ticket::factory()->for($membership->company)->create(['status' => 'open']);
        $otherCompany = Company::factory()->create();
        $otherTicket = Ticket::factory()->for($otherCompany)->create(['status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->from(route('app.tickets.show', $secondary->public_key))
            ->post(route('app.tickets.merge.store', $secondary->public_key), [
                'target_ticket_key' => $otherTicket->public_key,
            ])
            ->assertRedirect(route('app.tickets.show', $secondary->public_key))
            ->assertSessionHasErrors('target_ticket_key');

        $secondary->refresh();

        $this->assertFalse($secondary->merged);
        $this->assertSame('open', $secondary->status);
        $this->assertNull($secondary->merged_into_ticket_id);
    }

    public function test_ticket_detail_shows_merge_form_for_open_ticket(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($membership->company)->create(['status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee('Fusionar ticket')
            ->assertSee(route('app.tickets.merge.store', $ticket->public_key), false)
            ->assertSee('data-confirm="Fusionar este ticket lo marcará como fusionado y moverá futuras respuestas al principal. ¿Continuar?"', false);
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
