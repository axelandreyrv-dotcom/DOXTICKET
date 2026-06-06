<?php

namespace App\Mail\Admin;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Membership $membership,
        public ?string $passwordSetupUrl = null,
    ) {
        $this->membership->loadMissing(['company', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitacion a DoxTicket - '.$this->membership->company->name,
            tags: ['user-invitation'],
            metadata: [
                'membership_id' => (string) $this->membership->id,
                'company_id' => (string) $this->membership->company_id,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.admin.user-invitation-text',
        );
    }
}
