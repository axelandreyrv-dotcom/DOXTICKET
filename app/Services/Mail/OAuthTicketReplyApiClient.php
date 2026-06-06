<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OAuthTicketReplyApiClient
{
    public function sendTicketReply(MailAccount $account, Ticket $ticket, string $bodyText, array $attachments = []): void
    {
        if ($account->provider === 'gmail') {
            $this->sendGmail($account, $ticket, $bodyText, $attachments);

            return;
        }

        if ($account->provider === 'microsoft365') {
            $this->sendMicrosoft($account, $ticket, $bodyText, $attachments);

            return;
        }

        throw new RuntimeException('Unsupported OAuth mail provider.');
    }

    private function sendGmail(MailAccount $account, Ticket $ticket, string $bodyText, array $attachments): void
    {
        $response = Http::withToken((string) $account->oauth_access_token)
            ->acceptJson()
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $this->base64UrlEncode($this->rawMessage($account, $ticket, $bodyText, $attachments)),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'Gmail API send failed.');
        }
    }

    private function sendMicrosoft(MailAccount $account, Ticket $ticket, string $bodyText, array $attachments): void
    {
        $message = [
            'subject' => $this->subject($ticket),
            'body' => [
                'contentType' => 'Text',
                'content' => $bodyText,
            ],
            'toRecipients' => [[
                'emailAddress' => [
                    'address' => $ticket->requester_email,
                    'name' => $ticket->requester_name,
                ],
            ]],
            'replyTo' => [[
                'emailAddress' => [
                    'address' => $account->from_email,
                    'name' => $this->fromName($account),
                ],
            ]],
            'internetMessageHeaders' => [[
                'name' => 'X-DoxTicket-Key',
                'value' => $ticket->public_key,
            ]],
        ];

        if ($attachments !== []) {
            $message['attachments'] = array_map(fn (array $attachment): array => [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $attachment['filename'],
                'contentType' => $attachment['mime_type'],
                'contentBytes' => base64_encode($attachment['contents']),
            ], $attachments);
        }

        $response = Http::withToken((string) $account->oauth_access_token)
            ->acceptJson()
            ->post('https://graph.microsoft.com/v1.0/me/sendMail', [
                'message' => $message,
                'saveToSentItems' => true,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'Microsoft Graph sendMail failed.');
        }
    }

    private function rawMessage(MailAccount $account, Ticket $ticket, string $bodyText, array $attachments): string
    {
        $from = $this->address($account->from_email, $this->fromName($account));
        $to = $this->address((string) $ticket->requester_email, $ticket->requester_name);

        if ($attachments === []) {
            return implode("\r\n", [
                'From: '.$from,
                'To: '.$to,
                'Reply-To: '.$from,
                'Subject: '.$this->subject($ticket),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'X-DoxTicket-Key: '.$ticket->public_key,
                '',
                $bodyText,
                '',
            ]);
        }

        $boundary = 'doxticket-'.bin2hex(random_bytes(12));
        $parts = [
            'From: '.$from,
            'To: '.$to,
            'Reply-To: '.$from,
            'Subject: '.$this->subject($ticket),
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="'.$boundary.'"',
            'X-DoxTicket-Key: '.$ticket->public_key,
            '',
            '--'.$boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $bodyText,
            '',
        ];

        foreach ($attachments as $attachment) {
            $parts = array_merge($parts, [
                '--'.$boundary,
                'Content-Type: '.$attachment['mime_type'].'; name="'.$this->headerValue($attachment['filename']).'"',
                'Content-Disposition: attachment; filename="'.$this->headerValue($attachment['filename']).'"',
                'Content-Transfer-Encoding: base64',
                '',
                chunk_split(base64_encode($attachment['contents'])),
            ]);
        }

        $parts[] = '--'.$boundary.'--';
        $parts[] = '';

        return implode("\r\n", $parts);
    }

    private function subject(Ticket $ticket): string
    {
        return '['.$ticket->public_key.'] '.$ticket->subject;
    }

    private function fromName(MailAccount $account): string
    {
        return $account->from_name ?: (string) config('app.name', 'DoxTicket');
    }

    private function address(string $email, ?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '<'.$email.'>';
        }

        return '"'.addcslashes($name, '"\\').'" <'.$email.'>';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function headerValue(string $value): string
    {
        return addcslashes($value, '"\\');
    }
}
