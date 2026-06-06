<?php

namespace Tests\Feature\Mail;

use App\Mail\Tickets\TicketReplyMail;
use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_reply_to_ticket_requester_from_company_mail_account(): void
    {
        Mail::fake();

        [$user, $membership] = $this->tenantFixture();
        MailAccount::factory()->for($membership->company)->create([
            'from_name' => 'Mesa TI',
            'from_email' => 'soporte@example.test',
            'is_active' => true,
        ]);
        $ticket = Ticket::factory()->for($membership->company)->create([
            'requester_email' => 'ana@example.test',
            'requester_name' => 'Ana Mesa',
            'subject' => 'VPN oficina principal',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.replies.store', $ticket->public_key), [
                'body_text' => 'Hola Ana, reiniciamos el túnel VPN. Por favor intenta de nuevo.',
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key));

        $this->assertDatabaseHas('ticket_messages', [
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'author_user_id' => $user->id,
            'author_membership_id' => $membership->id,
            'author_email' => 'soporte@example.test',
            'author_name' => 'Mesa TI',
            'visibility' => 'public',
            'direction' => 'outbound',
            'body_text' => 'Hola Ana, reiniciamos el túnel VPN. Por favor intenta de nuevo.',
        ]);

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'actor_membership_id' => $membership->id,
            'type' => 'ticket.reply_sent',
        ]);

        Mail::assertSent(TicketReplyMail::class, function (TicketReplyMail $mail) use ($ticket): bool {
            return $mail->hasTo('ana@example.test')
                && $mail->hasFrom('soporte@example.test', 'Mesa TI')
                && $mail->hasReplyTo('soporte@example.test', 'Mesa TI')
                && $mail->hasSubject('['.$ticket->public_key.'] VPN oficina principal');
        });
    }

    public function test_agent_cannot_reply_without_active_company_mail_account(): void
    {
        Mail::fake();

        [$user, $membership] = $this->tenantFixture();
        $ticket = Ticket::factory()->for($membership->company)->create([
            'requester_email' => 'ana@example.test',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->from(route('app.tickets.show', $ticket->public_key))
            ->post(route('app.tickets.replies.store', $ticket->public_key), [
                'body_text' => 'Respuesta de prueba.',
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHasErrors('mail_account');

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
        ]);

        Mail::assertNothingSent();
    }

    public function test_agent_cannot_reply_without_requester_email(): void
    {
        Mail::fake();

        [$user, $membership] = $this->tenantFixture();
        MailAccount::factory()->for($membership->company)->create();
        $ticket = Ticket::factory()->for($membership->company)->create([
            'requester_email' => null,
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->from(route('app.tickets.show', $ticket->public_key))
            ->post(route('app.tickets.replies.store', $ticket->public_key), [
                'body_text' => 'Respuesta de prueba.',
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHasErrors('requester_email');

        Mail::assertNothingSent();
    }

    public function test_agent_can_reply_with_safe_attachments(): void
    {
        Mail::fake();
        Storage::fake('private');

        [$user, $membership] = $this->tenantFixture();
        MailAccount::factory()->for($membership->company)->create([
            'from_name' => 'Mesa TI',
            'from_email' => 'soporte@example.test',
            'is_active' => true,
        ]);
        $ticket = Ticket::factory()->for($membership->company)->create([
            'requester_email' => 'ana@example.test',
            'requester_name' => 'Ana Mesa',
            'subject' => 'Evidencia de respaldo',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.replies.store', $ticket->public_key), [
                'body_text' => 'Adjunto la evidencia solicitada.',
                'attachments' => [
                    UploadedFile::fake()->createWithContent('evidencia.png', 'fake-image'),
                    UploadedFile::fake()->createWithContent('inventario.xlsx', 'fake-excel'),
                ],
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHas('status', 'reply-sent');

        $message = $ticket->messages()->where('direction', 'outbound')->firstOrFail();

        $this->assertDatabaseHas('attachments', [
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message->id,
            'filename' => 'evidencia.png',
        ]);
        $this->assertDatabaseHas('attachments', [
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message->id,
            'filename' => 'inventario.xlsx',
        ]);

        Mail::assertSent(TicketReplyMail::class, function (TicketReplyMail $mail): bool {
            return collect($mail->replyAttachments)->pluck('filename')->all() === [
                'evidencia.png',
                'inventario.xlsx',
            ];
        });
    }

    public function test_dangerous_reply_attachment_is_blocked_before_sending(): void
    {
        Mail::fake();
        Storage::fake('private');

        [$user, $membership] = $this->tenantFixture();
        MailAccount::factory()->for($membership->company)->create(['is_active' => true]);
        $ticket = Ticket::factory()->for($membership->company)->create([
            'requester_email' => 'ana@example.test',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.replies.store', $ticket->public_key), [
                'body_text' => 'Adjunto archivo.',
                'attachments' => [
                    UploadedFile::fake()->createWithContent('limpieza.bat', '@echo off'),
                ],
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHasErrors('attachments');

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
        ]);
        Mail::assertNothingSent();
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create(['name' => 'QA Admin']);
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'agent', 'status' => 'active']);

        return [$user, $membership];
    }
}
