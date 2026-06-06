<?php

namespace App\Mail\Tickets;

use App\Models\MailAccount;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public MailAccount $mailAccount,
        public string $bodyText,
        public array $replyAttachments = [],
    ) {}

    public function envelope(): Envelope
    {
        $fromName = $this->mailAccount->from_name ?: config('app.name', 'DoxTicket');

        return new Envelope(
            from: new Address($this->mailAccount->from_email, $fromName),
            replyTo: [new Address($this->mailAccount->from_email, $fromName)],
            subject: '['.$this->ticket->public_key.'] '.$this->ticket->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.tickets.reply-text',
        );
    }

    public function attachments(): array
    {
        return array_map(
            fn (array $attachment): Attachment => Attachment::fromData(
                fn (): string => $attachment['contents'],
                $attachment['filename'],
            )->withMime($attachment['mime_type']),
            $this->replyAttachments,
        );
    }
}
