<?php

namespace App\Mail\Tickets;

use App\Models\MailAccount;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public MailAccount $mailAccount,
    ) {}

    public function envelope(): Envelope
    {
        $fromName = $this->mailAccount->from_name ?: config('app.name', 'DoxTicket');

        return new Envelope(
            from: new Address($this->mailAccount->from_email, $fromName),
            replyTo: [new Address($this->mailAccount->from_email, $fromName)],
            subject: '['.$this->ticket->public_key.'] Recibimos tu solicitud',
            tags: ['ticket-received'],
            metadata: [
                'ticket_id' => (string) $this->ticket->id,
                'public_key' => $this->ticket->public_key,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.tickets.received-text',
        );
    }
}
