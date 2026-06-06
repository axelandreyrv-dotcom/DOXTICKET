<?php

namespace Tests\Feature\Mail;

use App\Mail\Tickets\TicketReceivedMail;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use App\Services\Mail\InboundMailProcessor;
use App\Support\Mail\InboundMailAttachment;
use App\Support\Mail\InboundMailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InboundMailProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_message_creates_ticket_and_sanitized_message_for_mail_account_company(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'from_email' => 'soporte@example.test',
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<msg-1@example.test>',
            fromEmail: 'requester@example.test',
            fromName: 'Mesa Operaciones',
            subject: 'VPN caida',
            bodyText: 'No conecta la VPN.',
            bodyHtml: '<p>No conecta</p><script>alert(1)</script><a href="javascript:alert(1)">click</a><img src="https://tracker.example/pixel.png">',
            headers: ['Message-Id' => '<msg-1@example.test>'],
            deliveredAt: now(),
        ));

        $ticket = Ticket::withoutTenant()->firstOrFail();
        $message = TicketMessage::withoutTenant()->firstOrFail();

        $this->assertSame('created', $result->status);
        $this->assertTrue($result->ticket->is($ticket));
        $this->assertSame($account->company_id, $ticket->company_id);
        $this->assertSame($account->id, $ticket->mail_account_id);
        $this->assertSame('email', $ticket->source);
        $this->assertSame('new', $ticket->status);
        $this->assertSame('VPN caida', $ticket->subject);
        $this->assertSame('requester@example.test', $ticket->requester_email);
        $this->assertSame($ticket->id, $message->ticket_id);
        $this->assertSame('inbound', $message->direction);
        $this->assertSame('public', $message->visibility);
        $this->assertSame('<msg-1@example.test>', $message->message_id_header);
        $this->assertStringNotContainsString('<script', (string) $message->body_html);
        $this->assertStringNotContainsString('javascript:', (string) $message->body_html);
        $this->assertStringNotContainsString('<img', (string) $message->body_html);
        $this->assertTrue($message->external_images_blocked);
        $this->assertSame(['https://tracker.example/pixel.png'], $message->external_image_urls);
    }

    public function test_new_inbound_ticket_sends_received_confirmation_when_auto_reply_is_enabled(): void
    {
        Mail::fake();

        $account = MailAccount::factory()->for(Company::factory())->create([
            'from_email' => 'soporte@example.test',
            'from_name' => 'Mesa TI',
            'auto_reply_enabled' => true,
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<new-confirmation@example.test>',
            fromEmail: 'requester@example.test',
            fromName: 'Solicitante',
            subject: 'Laptop bloqueada',
            bodyText: 'No puedo entrar.',
            bodyHtml: null,
            headers: ['Message-Id' => '<new-confirmation@example.test>'],
            deliveredAt: now(),
        ));

        $this->assertSame('created', $result->status);

        Mail::assertSent(TicketReceivedMail::class, function ($mail) use ($account, $result): bool {
            return $mail->hasTo('requester@example.test')
                && $mail->hasFrom($account->from_email)
                && $mail->hasReplyTo($account->from_email)
                && $mail->hasSubject('['.$result->ticket->public_key.'] Recibimos tu solicitud');
        });
    }

    public function test_new_inbound_ticket_does_not_send_received_confirmation_when_auto_reply_is_disabled(): void
    {
        Mail::fake();

        $account = MailAccount::factory()->for(Company::factory())->create([
            'auto_reply_enabled' => false,
        ]);

        app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<disabled-confirmation@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Pantalla azul',
            bodyText: 'Se reinicia.',
            bodyHtml: null,
            headers: ['Message-Id' => '<disabled-confirmation@example.test>'],
            deliveredAt: now(),
        ));

        Mail::assertNotSent(TicketReceivedMail::class);
    }

    public function test_auto_reply_delivery_failure_records_internal_event_without_breaking_ingest(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'host_smtp' => 'smtp.invalid.test',
            'auto_reply_enabled' => true,
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<failed-confirmation@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'No imprime',
            bodyText: 'La impresora no responde.',
            bodyHtml: null,
            headers: ['Message-Id' => '<failed-confirmation@example.test>'],
            deliveredAt: now(),
        ));

        $this->assertSame('created', $result->status);
        $this->assertSame(1, Ticket::withoutTenant()->count());
        $this->assertTrue(TicketEvent::withoutTenant()
            ->where('company_id', $account->company_id)
            ->where('ticket_id', $result->ticket->id)
            ->where('type', 'mail.auto_reply_failed')
            ->exists());
    }

    public function test_inbound_reply_does_not_send_received_confirmation(): void
    {
        Mail::fake();

        $account = MailAccount::factory()->for(Company::factory())->create([
            'auto_reply_enabled' => true,
        ]);
        $ticket = Ticket::factory()->for($account->company)->create([
            'mail_account_id' => $account->id,
            'source' => 'email',
            'status' => 'open',
        ]);

        app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<reply-no-confirmation@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Re: ['.$ticket->public_key.'] Laptop bloqueada',
            bodyText: 'Adjunto mas informacion.',
            bodyHtml: null,
            headers: ['Message-Id' => '<reply-no-confirmation@example.test>'],
            deliveredAt: now(),
        ));

        Mail::assertNotSent(TicketReceivedMail::class);
    }

    public function test_inbound_message_stores_safe_attachments_privately(): void
    {
        Storage::fake('private');

        $account = MailAccount::factory()->for(Company::factory())->create([
            'auto_reply_enabled' => false,
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<attachment@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Adjunto diagnostico',
            bodyText: 'Envio evidencia.',
            bodyHtml: null,
            headers: ['Message-Id' => '<attachment@example.test>'],
            deliveredAt: now(),
            attachments: [
                new InboundMailAttachment(
                    filename: 'diagnostico.txt',
                    mimeType: 'text/plain',
                    contents: 'Resultado de diagnostico.',
                ),
            ],
        ));

        $attachment = Attachment::withoutTenant()->firstOrFail();
        $message = TicketMessage::withoutTenant()->firstOrFail();

        $this->assertSame('created', $result->status);
        $this->assertSame($account->company_id, $attachment->company_id);
        $this->assertSame($result->ticket->id, $attachment->ticket_id);
        $this->assertSame($message->id, $attachment->ticket_message_id);
        $this->assertSame('diagnostico.txt', $attachment->filename);
        $this->assertSame('text/plain', $attachment->mime_type);
        $this->assertSame('private', $attachment->disk);
        $this->assertSame(hash('sha256', 'Resultado de diagnostico.'), $attachment->checksum_sha256);
        Storage::disk('private')->assertExists($attachment->path);

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $account->company_id,
            'ticket_id' => $result->ticket->id,
            'type' => 'ticket.attachment_added',
        ]);
    }

    public function test_inbound_dangerous_attachment_is_blocked_and_recorded_as_internal_event(): void
    {
        Storage::fake('private');

        $account = MailAccount::factory()->for(Company::factory())->create([
            'auto_reply_enabled' => false,
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<blocked-attachment@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Script sospechoso',
            bodyText: 'Adjunto archivo.',
            bodyHtml: null,
            headers: ['Message-Id' => '<blocked-attachment@example.test>'],
            deliveredAt: now(),
            attachments: [
                new InboundMailAttachment(
                    filename: 'limpieza.bat',
                    mimeType: 'application/x-msdownload',
                    contents: '@echo off',
                ),
            ],
        ));

        $this->assertSame('created', $result->status);
        $this->assertSame(0, Attachment::withoutTenant()->count());

        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $account->company_id,
            'ticket_id' => $result->ticket->id,
            'type' => 'ticket.attachment_blocked',
        ]);
    }

    public function test_inbound_oversized_attachment_is_blocked_without_breaking_ingest(): void
    {
        Storage::fake('private');
        Config::set('doxticket.attachments.max_bytes', 5);

        $account = MailAccount::factory()->for(Company::factory())->create([
            'auto_reply_enabled' => false,
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<oversized-attachment@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Adjunto pesado',
            bodyText: 'Envio archivo.',
            bodyHtml: null,
            headers: ['Message-Id' => '<oversized-attachment@example.test>'],
            deliveredAt: now(),
            attachments: [
                new InboundMailAttachment(
                    filename: 'evidencia.txt',
                    mimeType: 'text/plain',
                    contents: 'demasiado grande',
                ),
            ],
        ));

        $this->assertSame('created', $result->status);
        $this->assertSame(1, Ticket::withoutTenant()->count());
        $this->assertSame(1, TicketMessage::withoutTenant()->count());
        $this->assertSame(0, Attachment::withoutTenant()->count());

        $event = TicketEvent::withoutTenant()
            ->where('company_id', $account->company_id)
            ->where('ticket_id', $result->ticket->id)
            ->where('type', 'ticket.attachment_blocked')
            ->firstOrFail();

        $this->assertSame('file_too_large', $event->payload['reason']);
        $this->assertSame(5, $event->payload['max_size_bytes']);
    }

    public function test_duplicate_message_id_is_ignored_without_creating_another_message(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $message = new InboundMailMessage(
            messageId: '<duplicate@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Monitor sin imagen',
            bodyText: 'Sin imagen.',
            bodyHtml: null,
            headers: ['Message-Id' => '<duplicate@example.test>'],
            deliveredAt: now(),
        );

        app(InboundMailProcessor::class)->process($account, $message);
        $result = app(InboundMailProcessor::class)->process($account, $message);

        $this->assertSame('duplicate', $result->status);
        $this->assertSame(1, Ticket::withoutTenant()->count());
        $this->assertSame(1, TicketMessage::withoutTenant()->count());
    }

    public function test_inbound_message_without_message_id_is_deduplicated_by_fingerprint(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $message = new InboundMailMessage(
            messageId: null,
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Sin message id',
            bodyText: 'Mismo contenido.',
            bodyHtml: null,
            headers: [],
            deliveredAt: now(),
        );

        app(InboundMailProcessor::class)->process($account, $message);
        $result = app(InboundMailProcessor::class)->process($account, $message);

        $storedMessage = TicketMessage::withoutTenant()->firstOrFail();

        $this->assertSame('duplicate', $result->status);
        $this->assertSame('message_fingerprint', $result->reason);
        $this->assertSame(1, Ticket::withoutTenant()->count());
        $this->assertSame(1, TicketMessage::withoutTenant()->count());
        $this->assertStringStartsWith('dox-fingerprint:', (string) $storedMessage->message_id_header);
    }

    public function test_inbound_message_with_no_useful_body_gets_safe_placeholder(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'auto_reply_enabled' => false,
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<empty-body@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Sin cuerpo',
            bodyText: " \n\t ",
            bodyHtml: '<script>alert(1)</script><img src="https://tracker.example/pixel.png">',
            headers: ['Message-Id' => '<empty-body@example.test>'],
            deliveredAt: now(),
        ));

        $message = TicketMessage::withoutTenant()->firstOrFail();

        $this->assertSame('created', $result->status);
        $this->assertSame('Este correo no incluía contenido visible.', $message->body_text);
        $this->assertNull($message->body_html);
        $this->assertTrue($message->external_images_blocked);
        $this->assertSame(['https://tracker.example/pixel.png'], $message->external_image_urls);
    }

    public function test_reply_threads_by_public_key_and_returns_resolved_ticket_to_open(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $ticket = Ticket::factory()->for($account->company)->create([
            'mail_account_id' => $account->id,
            'subject' => 'Impresora',
            'status' => 'resolved',
            'source' => 'email',
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<reply@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Re: ['.$ticket->public_key.'] Impresora',
            bodyText: 'Sigue fallando.',
            bodyHtml: null,
            headers: ['Message-Id' => '<reply@example.test>'],
            deliveredAt: now(),
        ));

        $ticket->refresh();

        $this->assertSame('appended', $result->status);
        $this->assertSame('open', $ticket->status);
        $this->assertSame(1, Ticket::withoutTenant()->count());
        $this->assertSame(1, $ticket->messages()->count());
    }

    public function test_reply_to_merged_ticket_is_appended_to_primary_ticket(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $primary = Ticket::factory()->for($account->company)->create([
            'mail_account_id' => $account->id,
            'status' => 'open',
            'source' => 'email',
        ]);
        $secondary = Ticket::factory()->for($account->company)->create([
            'mail_account_id' => $account->id,
            'status' => 'merged',
            'source' => 'email',
            'merged' => true,
            'merged_into_ticket_id' => $primary->id,
            'merged_at' => now(),
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<merged-reply@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Re: ['.$secondary->public_key.'] duplicado',
            bodyText: 'Respuesta al duplicado.',
            bodyHtml: null,
            headers: ['Message-Id' => '<merged-reply@example.test>'],
            deliveredAt: now(),
        ));

        $this->assertSame('appended', $result->status);
        $this->assertTrue($result->ticket->is($primary->fresh()));
        $this->assertSame(1, $primary->messages()->count());
        $this->assertSame(0, $secondary->messages()->count());
    }

    public function test_reply_threads_by_references_header_within_same_company_only(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $otherAccount = MailAccount::factory()->for(Company::factory())->create();
        $otherTicket = Ticket::factory()->for($otherAccount->company)->create(['source' => 'email']);
        TicketMessage::withoutTenant()->create([
            'company_id' => $otherAccount->company_id,
            'ticket_id' => $otherTicket->id,
            'visibility' => 'public',
            'direction' => 'inbound',
            'body_text' => 'Other',
            'message_id_header' => '<shared-ref@example.test>',
        ]);

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<same-ref-reply@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Re: unrelated',
            bodyText: 'Debe crear ticket nuevo en esta empresa.',
            bodyHtml: null,
            headers: ['Message-Id' => '<same-ref-reply@example.test>'],
            inReplyTo: '<shared-ref@example.test>',
            references: '<shared-ref@example.test>',
            deliveredAt: now(),
        ));

        $this->assertSame('created', $result->status);
        $this->assertSame($account->company_id, $result->ticket->company_id);
        $this->assertSame(2, Ticket::withoutTenant()->count());
    }

    public function test_auto_submitted_message_is_ignored_as_loop(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();

        $result = app(InboundMailProcessor::class)->process($account, new InboundMailMessage(
            messageId: '<auto@example.test>',
            fromEmail: 'mailer-daemon@example.test',
            fromName: null,
            subject: 'Auto reply',
            bodyText: 'Out of office',
            bodyHtml: null,
            headers: ['Auto-Submitted' => 'auto-replied', 'Message-Id' => '<auto@example.test>'],
            deliveredAt: now(),
        ));

        $this->assertSame('ignored_loop', $result->status);
        $this->assertSame(0, Ticket::withoutTenant()->count());
        $this->assertSame(0, TicketMessage::withoutTenant()->count());
    }
}
