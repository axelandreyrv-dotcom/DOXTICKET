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
            ->assertSee('Responder')
            ->assertSee(route('app.tickets.replies.store', $ticket->public_key), false)
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

    public function test_ticket_detail_exposes_copy_action_for_visible_ticket_key(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['subject' => 'Clave facil de copiar']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee('data-copy-text="'.$ticket->public_key.'"', false)
            ->assertSee('aria-label="Copiar clave '.$ticket->public_key.'"', false)
            ->assertSee('data-copy-success="Copiado"', false)
            ->assertSee('role="status"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_ticket_detail_shows_human_readable_activity_labels(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['status' => 'open']);

        $ticket->events()->create([
            'company_id' => $activeMembership->company_id,
            'actor_user_id' => $activeMembership->user_id,
            'actor_membership_id' => $activeMembership->id,
            'type' => 'ticket.note_added',
            'payload' => ['visibility' => 'internal'],
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee('Nota interna agregada')
            ->assertDontSee('ticket.note_added');
    }

    public function test_ticket_detail_shows_blocked_external_images_as_explicit_open_links(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['subject' => 'Correo con tracking']);

        TicketMessage::withoutTenant()->create([
            'company_id' => $activeMembership->company_id,
            'ticket_id' => $ticket->id,
            'author_email' => 'solicitante@example.test',
            'author_name' => 'Solicitante',
            'visibility' => 'public',
            'direction' => 'inbound',
            'body_text' => 'Adjunto captura.',
            'external_images_blocked' => true,
            'external_image_urls' => ['https://tracker.example/pixel.png'],
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee('Imágenes externas bloqueadas por privacidad')
            ->assertSee('Abrir imagen 1')
            ->assertSee('https://tracker.example/pixel.png', false);
    }

    public function test_ticket_thread_shows_professional_message_direction_labels(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['subject' => 'Hilo con estados claros']);

        TicketMessage::withoutTenant()->create([
            'company_id' => $activeMembership->company_id,
            'ticket_id' => $ticket->id,
            'author_email' => 'ana.mesa@example.test',
            'author_name' => 'Ana Mesa',
            'visibility' => 'public',
            'direction' => 'inbound',
            'body_text' => 'No puedo entrar a la VPN.',
        ]);

        TicketMessage::withoutTenant()->create([
            'company_id' => $activeMembership->company_id,
            'ticket_id' => $ticket->id,
            'author_user_id' => $activeMembership->user_id,
            'author_membership_id' => $activeMembership->id,
            'author_email' => 'soporte@example.test',
            'author_name' => 'Soporte TI',
            'visibility' => 'public',
            'direction' => 'outbound',
            'body_text' => 'Revisa el cliente VPN actualizado.',
        ]);

        TicketMessage::withoutTenant()->create([
            'company_id' => $activeMembership->company_id,
            'ticket_id' => $ticket->id,
            'author_user_id' => $activeMembership->user_id,
            'author_membership_id' => $activeMembership->id,
            'author_name' => 'QA Admin',
            'visibility' => 'internal',
            'direction' => 'internal',
            'body_text' => 'Confirmar licencia antes de cerrar.',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee('Correo entrante')
            ->assertSee('Respuesta enviada')
            ->assertSee('Nota interna')
            ->assertSee('ana.mesa@example.test')
            ->assertSee('soporte@example.test');
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

    public function test_ticket_detail_validation_errors_are_announced_and_associated_to_fields(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['status' => 'open']);

        $this->followingRedirects()
            ->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->from(route('app.tickets.show', $ticket->public_key))
            ->post(route('app.tickets.merge.store', $ticket->public_key), [
                'target_ticket_key' => '',
            ])
            ->assertOk()
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="target_ticket_key-error"', false)
            ->assertSee('id="target_ticket_key-error"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('El campo ticket principal es obligatorio.');
    }

    public function test_ticket_detail_forms_use_explicit_autocomplete_and_spellcheck_metadata(): void
    {
        [$user, $activeMembership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($activeMembership->company)->create(['status' => 'open']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $activeMembership->id])
            ->get(route('app.tickets.show', $ticket->public_key))
            ->assertOk()
            ->assertSee('id="reply_body_text" name="body_text" rows="6" required autocomplete="off"', false)
            ->assertSee('id="body_text" name="body_text" rows="5" required autocomplete="off"', false)
            ->assertSee('id="target_ticket_key" name="target_ticket_key" type="text" inputmode="text" autocomplete="off" spellcheck="false"', false);
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
