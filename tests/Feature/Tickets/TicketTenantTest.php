<?php

namespace Tests\Feature\Tickets;

use App\Models\Company;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_queries_are_scoped_to_the_active_company(): void
    {
        [$user, $activeCompany, $otherCompany] = $this->tenantFixture();

        Ticket::factory()->for($activeCompany)->create(['subject' => 'VPN caido']);
        Ticket::factory()->for($otherCompany)->create(['subject' => 'Impresora lenta']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $user->memberships()->whereBelongsTo($activeCompany)->value('id')])
            ->get('/app/tickets')
            ->assertOk()
            ->assertSee('VPN caido')
            ->assertDontSee('Impresora lenta');
    }

    public function test_manual_ticket_creation_ignores_untrusted_company_id_input(): void
    {
        [$user, $activeCompany, $otherCompany] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $user->memberships()->whereBelongsTo($activeCompany)->value('id')])
            ->post('/app/tickets', [
                'company_id' => $otherCompany->id,
                'subject' => 'Alta de monitor',
                'body_text' => 'El usuario necesita un monitor adicional.',
                'requester_email' => 'solicitante@example.test',
                'requester_name' => 'Solicitante Mesa',
                'priority' => 'high',
            ])
            ->assertRedirect();

        $ticket = Ticket::withoutTenant()->where('subject', 'Alta de monitor')->firstOrFail();

        $this->assertSame($activeCompany->id, $ticket->company_id);
        $this->assertSame('DT-1', $ticket->public_key);
        $this->assertSame('manual', $ticket->source);
        $this->assertDatabaseHas('ticket_messages', [
            'company_id' => $activeCompany->id,
            'ticket_id' => $ticket->id,
            'body_text' => 'El usuario necesita un monitor adicional.',
            'direction' => 'internal',
            'visibility' => 'internal',
        ]);
    }

    /**
     * @return array{User, Company, Company}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create();
        $activeCompany = Company::factory()->create(['name' => 'Dox IT']);
        $otherCompany = Company::factory()->create(['name' => 'Otra Empresa']);

        Membership::factory()->for($user)->for($activeCompany)->create(['role' => 'agent', 'status' => 'active']);
        Membership::factory()->for(User::factory())->for($otherCompany)->create(['role' => 'agent', 'status' => 'active']);

        return [$user, $activeCompany, $otherCompany];
    }
}
