<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use App\Support\Mail\InboundMailMessage;
use App\Support\Mail\InboundMailResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboundMailProcessor
{
    public function process(MailAccount $account, InboundMailMessage $message): InboundMailResult
    {
        if ($this->isLoop($account, $message)) {
            return new InboundMailResult('ignored_loop', reason: 'auto-submitted');
        }

        if ($message->messageId !== null) {
            $duplicate = TicketMessage::withoutTenant()
                ->with('ticket')
                ->where('company_id', $account->company_id)
                ->where('message_id_header', $message->messageId)
                ->first();

            if ($duplicate !== null) {
                return new InboundMailResult('duplicate', $duplicate->ticket, 'message_id');
            }
        }

        return DB::transaction(function () use ($account, $message): InboundMailResult {
            $ticket = $this->findThreadTicket($account, $message);
            $status = 'appended';

            if ($ticket === null) {
                $ticket = Ticket::withoutTenant()->create([
                    'company_id' => $account->company_id,
                    'mail_account_id' => $account->id,
                    'requester_email' => Str::lower($message->fromEmail),
                    'requester_name' => $message->fromName,
                    'subject' => $this->subjectWithoutMarker($message->normalizedSubject()),
                    'status' => 'new',
                    'priority' => 'medium',
                    'source' => 'email',
                    'external_thread_id' => $message->messageId,
                    'last_activity_at' => now(),
                ]);
                $status = 'created';
            } else {
                $ticket->forceFill([
                    'status' => in_array($ticket->status, ['resolved', 'closed'], true) ? 'reopened' : 'open',
                    'last_activity_at' => now(),
                ])->save();
            }

            $sanitized = $this->sanitizeHtml($message->bodyHtml);

            TicketMessage::withoutTenant()->create([
                'company_id' => $account->company_id,
                'ticket_id' => $ticket->id,
                'author_email' => Str::lower($message->fromEmail),
                'author_name' => $message->fromName,
                'visibility' => 'public',
                'direction' => 'inbound',
                'body_html' => $sanitized['html'],
                'body_text' => $message->bodyText,
                'external_images_blocked' => $sanitized['external_images_blocked'],
                'message_id_header' => $message->messageId,
                'in_reply_to_header' => $message->inReplyTo,
                'references_header' => $message->references,
                'headers_raw' => $this->redactedHeaders($message->headers),
                'delivered_at' => $message->deliveredAt,
            ]);

            TicketEvent::withoutTenant()->create([
                'company_id' => $account->company_id,
                'ticket_id' => $ticket->id,
                'type' => $status === 'created' ? 'mail.ticket_created' : 'mail.reply_received',
                'payload' => [
                    'mail_account_id' => $account->id,
                    'message_id' => $message->messageId,
                    'auto_reply_enabled' => $status === 'created' && $account->auto_reply_enabled,
                ],
            ]);

            return new InboundMailResult($status, $ticket->refresh());
        });
    }

    private function findThreadTicket(MailAccount $account, InboundMailMessage $message): ?Ticket
    {
        $publicKey = $this->publicKeyFromSubject($message->normalizedSubject());

        if ($publicKey !== null) {
            $ticket = Ticket::withoutTenant()
                ->where('company_id', $account->company_id)
                ->where('public_key', $publicKey)
                ->first();

            if ($ticket !== null) {
                return $ticket;
            }
        }

        $messageIds = $this->threadMessageIds($message);

        if ($messageIds === []) {
            return null;
        }

        $threadMessage = TicketMessage::withoutTenant()
            ->with('ticket')
            ->where('company_id', $account->company_id)
            ->whereIn('message_id_header', $messageIds)
            ->orderByDesc('id')
            ->first();

        return $threadMessage?->ticket;
    }

    private function isLoop(MailAccount $account, InboundMailMessage $message): bool
    {
        if (Str::lower($message->fromEmail) === Str::lower($account->from_email)) {
            return true;
        }

        $headers = array_change_key_case($message->headers, CASE_LOWER);
        $autoSubmitted = Str::lower($headers['auto-submitted'] ?? 'no');

        if ($autoSubmitted !== 'no') {
            return true;
        }

        $precedence = Str::lower($headers['precedence'] ?? '');

        return in_array($precedence, ['bulk', 'junk', 'list'], true);
    }

    /**
     * @return array{html: ?string, external_images_blocked: bool}
     */
    private function sanitizeHtml(?string $html): array
    {
        if ($html === null) {
            return ['html' => null, 'external_images_blocked' => false];
        }

        $externalImagesBlocked = preg_match('/<img\b[^>]*\bsrc=["\']?https?:\/\//i', $html) === 1;
        $html = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html) ?? '';
        $html = preg_replace('/<img\b[^>]*\bsrc=["\']?https?:\/\/[^"\'>\s]+["\']?[^>]*>/i', '', $html) ?? '';
        $html = preg_replace('/(<a\b[^>]*\bhref\s*=\s*["\']?)\s*javascript:[^"\'>\s]*(["\']?[^>]*>)/i', '<a>', $html) ?? '';
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/i', '', $html) ?? '';
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><blockquote><ul><ol><li><a><code><pre>');

        return ['html' => trim($html), 'external_images_blocked' => $externalImagesBlocked];
    }

    /**
     * @return array<string, string>
     */
    private function redactedHeaders(array $headers): array
    {
        $redacted = [];

        foreach ($headers as $key => $value) {
            $header = (string) $key;
            $redacted[$header] = preg_match('/authorization|cookie|token|secret|password/i', $header) === 1
                ? '[redacted]'
                : (string) $value;
        }

        return $redacted;
    }

    private function publicKeyFromSubject(string $subject): ?string
    {
        if (preg_match('/\[(DT-\d+)\]/i', $subject, $matches) !== 1) {
            return null;
        }

        return Str::upper($matches[1]);
    }

    private function subjectWithoutMarker(string $subject): string
    {
        $clean = trim((string) preg_replace('/\s*\[DT-\d+\]\s*/i', ' ', $subject));

        return $clean === '' ? 'Sin Asunto' : $clean;
    }

    /**
     * @return list<string>
     */
    private function threadMessageIds(InboundMailMessage $message): array
    {
        $raw = trim((string) $message->inReplyTo).' '.trim((string) $message->references);

        if ($raw === '') {
            return [];
        }

        preg_match_all('/<[^>]+>/', $raw, $matches);

        return array_values(array_unique($matches[0]));
    }
}
