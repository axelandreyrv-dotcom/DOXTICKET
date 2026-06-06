<?php

namespace App\Services\Mail;

use App\Mail\Tickets\TicketReceivedMail;
use App\Models\MailAccount;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use App\Services\Tickets\TicketAttachmentService;
use App\Support\Mail\InboundMailMessage;
use App\Support\Mail\InboundMailResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class InboundMailProcessor
{
    public function __construct(
        private readonly TenantMailerConfigurator $mailerConfigurator,
        private readonly TicketAttachmentService $attachmentService,
    ) {}

    public function process(MailAccount $account, InboundMailMessage $message): InboundMailResult
    {
        if ($this->isLoop($account, $message)) {
            return new InboundMailResult('ignored_loop', reason: 'auto-submitted');
        }

        $dedupeKey = $this->dedupeKey($account, $message);
        $duplicate = TicketMessage::withoutTenant()
            ->with('ticket')
            ->where('company_id', $account->company_id)
            ->where('message_id_header', $dedupeKey)
            ->first();

        if ($duplicate !== null) {
            return new InboundMailResult('duplicate', $duplicate->ticket, $message->messageId !== null ? 'message_id' : 'message_fingerprint');
        }

        $result = DB::transaction(function () use ($account, $message, $dedupeKey): InboundMailResult {
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
                    'external_thread_id' => $dedupeKey,
                    'last_activity_at' => now(),
                ]);
                $status = 'created';
            } else {
                $ticket->forceFill([
                    'status' => 'open',
                    'last_activity_at' => now(),
                ])->save();
            }

            $sanitized = $this->sanitizeHtml($message->bodyHtml);
            $bodyText = $this->bodyText($message->bodyText, $sanitized['html']);

            $ticketMessage = TicketMessage::withoutTenant()->create([
                'company_id' => $account->company_id,
                'ticket_id' => $ticket->id,
                'author_email' => Str::lower($message->fromEmail),
                'author_name' => $message->fromName,
                'visibility' => 'public',
                'direction' => 'inbound',
                'body_html' => $sanitized['html'],
                'body_text' => $bodyText,
                'external_images_blocked' => $sanitized['external_images_blocked'],
                'external_image_urls' => $sanitized['external_image_urls'],
                'message_id_header' => $dedupeKey,
                'in_reply_to_header' => $message->inReplyTo,
                'references_header' => $message->references,
                'headers_raw' => $this->redactedHeaders($message->headers),
                'delivered_at' => $message->deliveredAt,
            ]);

            foreach ($message->attachments as $attachment) {
                $this->attachmentService->storeContent(
                    ticket: $ticket,
                    actor: null,
                    message: $ticketMessage,
                    filename: $attachment->filename,
                    mimeType: $attachment->mimeType,
                    contents: $attachment->contents,
                );
            }

            TicketEvent::withoutTenant()->create([
                'company_id' => $account->company_id,
                'ticket_id' => $ticket->id,
                'type' => $status === 'created' ? 'mail.ticket_created' : 'mail.reply_received',
                'payload' => [
                    'mail_account_id' => $account->id,
                    'message_id' => $message->messageId,
                    'dedupe_key' => $message->messageId === null ? $dedupeKey : null,
                    'auto_reply_enabled' => $status === 'created' && $account->auto_reply_enabled,
                ],
            ]);

            return new InboundMailResult($status, $ticket->refresh());
        });

        if ($result->status === 'created' && $account->auto_reply_enabled) {
            $this->sendReceivedConfirmation($account, $result->ticket);
        }

        return $result;
    }

    private function dedupeKey(MailAccount $account, InboundMailMessage $message): string
    {
        if ($message->messageId !== null && trim($message->messageId) !== '') {
            return trim($message->messageId);
        }

        $fingerprint = implode('|', [
            $account->id,
            Str::lower($message->fromEmail),
            $message->normalizedSubject(),
            trim((string) $message->bodyText),
            trim((string) $message->bodyHtml),
            $message->deliveredAt?->toIso8601String() ?? '',
        ]);

        return 'dox-fingerprint:'.hash('sha256', $fingerprint);
    }

    private function sendReceivedConfirmation(MailAccount $account, Ticket $ticket): void
    {
        try {
            $this->mailerConfigurator->configure($account);

            Mail::mailer('tenant_smtp')
                ->to($ticket->requester_email, $ticket->requester_name)
                ->send(new TicketReceivedMail($ticket, $account));
        } catch (Throwable $exception) {
            TicketEvent::withoutTenant()->create([
                'company_id' => $account->company_id,
                'ticket_id' => $ticket->id,
                'type' => 'mail.auto_reply_failed',
                'payload' => [
                    'mail_account_id' => $account->id,
                    'error' => Str::limit($exception->getMessage(), 240, ''),
                ],
            ]);
        }
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
                return $this->primaryTicket($ticket);
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

        return $threadMessage?->ticket ? $this->primaryTicket($threadMessage->ticket) : null;
    }

    private function primaryTicket(Ticket $ticket): Ticket
    {
        if (! $ticket->merged || $ticket->merged_into_ticket_id === null) {
            return $ticket;
        }

        return Ticket::withoutTenant()
            ->where('company_id', $ticket->company_id)
            ->whereKey($ticket->merged_into_ticket_id)
            ->first() ?? $ticket;
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
     * @return array{html: ?string, external_images_blocked: bool, external_image_urls: list<string>}
     */
    private function sanitizeHtml(?string $html): array
    {
        if ($html === null) {
            return ['html' => null, 'external_images_blocked' => false, 'external_image_urls' => []];
        }

        $externalImageUrls = $this->externalImageUrls($html);
        $html = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html) ?? '';
        $html = preg_replace('/<img\b[^>]*\bsrc=["\']?https?:\/\/[^"\'>\s]+["\']?[^>]*>/i', '', $html) ?? '';
        $html = preg_replace('/(<a\b[^>]*\bhref\s*=\s*["\']?)\s*javascript:[^"\'>\s]*(["\']?[^>]*>)/i', '<a>', $html) ?? '';
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/i', '', $html) ?? '';
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><blockquote><ul><ol><li><a><code><pre>');

        $html = trim($html);

        return [
            'html' => $html === '' ? null : $html,
            'external_images_blocked' => $externalImageUrls !== [],
            'external_image_urls' => $externalImageUrls,
        ];
    }

    /**
     * @return list<string>
     */
    private function externalImageUrls(string $html): array
    {
        preg_match_all('/<img\b[^>]*\bsrc\s*=\s*(["\']?)(https?:\/\/[^"\'>\s]+)\1[^>]*>/i', $html, $matches);

        $urls = [];

        foreach ($matches[2] ?? [] as $url) {
            $decoded = html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (Str::startsWith(Str::lower($decoded), ['http://', 'https://'])) {
                $urls[] = $decoded;
            }
        }

        return array_slice(array_values(array_unique($urls)), 0, 20);
    }

    private function bodyText(?string $bodyText, ?string $bodyHtml): string
    {
        $text = trim((string) $bodyText);

        if ($text !== '') {
            return $text;
        }

        if (trim(strip_tags((string) $bodyHtml)) !== '') {
            return '';
        }

        return 'Este correo no incluía contenido visible.';
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
