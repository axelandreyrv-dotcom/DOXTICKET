<?php

namespace Tests\Feature\Mail;

use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\Mail\InboundMailProcessor;
use App\Support\Mail\InboundMailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertTrue($message->external_images_blocked);
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

    public function test_reply_threads_by_public_key_and_reopens_resolved_ticket(): void
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
        $this->assertSame('reopened', $ticket->status);
        $this->assertSame(1, Ticket::withoutTenant()->count());
        $this->assertSame(1, $ticket->messages()->count());
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
