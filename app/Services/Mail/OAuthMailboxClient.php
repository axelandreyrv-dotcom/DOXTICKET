<?php

namespace App\Services\Mail;

use App\Contracts\Mail\MailboxClient;
use App\Models\MailAccount;
use App\Support\Mail\FetchedMailMessage;
use App\Support\Mail\InboundMailAttachment;
use App\Support\Mail\InboundMailMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OAuthMailboxClient implements MailboxClient
{
    public function fetchNewMessages(MailAccount $account): iterable
    {
        if ($account->provider === 'gmail') {
            return $this->fetchGmail($account);
        }

        if ($account->provider === 'microsoft365') {
            return $this->fetchMicrosoft($account);
        }

        return [];
    }

    /**
     * @return list<FetchedMailMessage>
     */
    private function fetchGmail(MailAccount $account): array
    {
        $response = Http::withToken((string) $account->oauth_access_token)
            ->acceptJson()
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', [
                'labelIds' => 'INBOX',
                'maxResults' => 25,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'Gmail API list messages failed.');
        }

        $ids = [];
        foreach ($response->json('messages', []) as $message) {
            $id = $message['id'] ?? null;

            if (! is_string($id) || $id === '') {
                continue;
            }

            if ($account->last_uid !== null && $id === $account->last_uid) {
                break;
            }

            $ids[] = $id;
        }

        $fetched = [];
        foreach (array_reverse($ids) as $id) {
            $message = $this->getGmailMessage($account, $id);
            $headers = $this->gmailHeaders($message);
            $from = $this->parseAddress($headers['From'] ?? '');

            if ($from['email'] === null) {
                continue;
            }

            $fetched[] = new FetchedMailMessage($id, new InboundMailMessage(
                messageId: $headers['Message-ID'] ?? $headers['Message-Id'] ?? null,
                fromEmail: $from['email'],
                fromName: $from['name'],
                subject: $this->decodeHeader($headers['Subject'] ?? null),
                bodyText: $this->gmailBody($message, 'text/plain'),
                bodyHtml: $this->gmailBody($message, 'text/html'),
                headers: $headers,
                inReplyTo: $headers['In-Reply-To'] ?? null,
                references: $headers['References'] ?? null,
                deliveredAt: $this->parseDate($headers['Date'] ?? null),
                attachments: $this->gmailAttachments($account, $id, $message['payload'] ?? []),
            ));
        }

        return $fetched;
    }

    /**
     * @return list<FetchedMailMessage>
     */
    private function fetchMicrosoft(MailAccount $account): array
    {
        $response = Http::withToken((string) $account->oauth_access_token)
            ->withHeaders(['Prefer' => 'outlook.body-content-type="text"'])
            ->acceptJson()
            ->get('https://graph.microsoft.com/v1.0/me/mailFolders/Inbox/messages', [
                '$top' => 25,
                '$orderby' => 'receivedDateTime desc',
                '$select' => 'id,internetMessageId,subject,from,body,receivedDateTime,internetMessageHeaders',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'Microsoft Graph list messages failed.');
        }

        $messages = [];
        foreach ($response->json('value', []) as $message) {
            $id = $message['id'] ?? null;

            if (! is_string($id) || $id === '') {
                continue;
            }

            if ($account->last_uid !== null && $id === $account->last_uid) {
                break;
            }

            $messages[] = $message;
        }

        $fetched = [];
        foreach (array_reverse($messages) as $message) {
            $from = $message['from']['emailAddress'] ?? [];
            $email = is_string($from['address'] ?? null) ? Str::lower($from['address']) : null;

            if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $headers = $this->graphHeaders($message['internetMessageHeaders'] ?? []);
            $id = (string) $message['id'];

            $fetched[] = new FetchedMailMessage($id, new InboundMailMessage(
                messageId: is_string($message['internetMessageId'] ?? null) ? $message['internetMessageId'] : null,
                fromEmail: $email,
                fromName: is_string($from['name'] ?? null) ? $from['name'] : null,
                subject: is_string($message['subject'] ?? null) ? $message['subject'] : null,
                bodyText: $this->graphBody($message),
                bodyHtml: null,
                headers: $headers,
                inReplyTo: $headers['In-Reply-To'] ?? null,
                references: $headers['References'] ?? null,
                deliveredAt: $this->parseDate(is_string($message['receivedDateTime'] ?? null) ? $message['receivedDateTime'] : null),
                attachments: $this->graphAttachments($account, $id, (bool) ($message['hasAttachments'] ?? false)),
            ));
        }

        return $fetched;
    }

    /**
     * @return array<string, mixed>
     */
    private function getGmailMessage(MailAccount $account, string $id): array
    {
        $response = Http::withToken((string) $account->oauth_access_token)
            ->acceptJson()
            ->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$id}", [
                'format' => 'full',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'Gmail API get message failed.');
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, string>
     */
    private function gmailHeaders(array $message): array
    {
        $headers = [];

        foreach ($message['payload']['headers'] ?? [] as $header) {
            if (! is_array($header) || ! is_string($header['name'] ?? null) || ! is_string($header['value'] ?? null)) {
                continue;
            }

            $headers[$this->canonicalHeaderName($header['name'])] = $header['value'];
        }

        return $headers;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function gmailBody(array $message, string $mimeType): ?string
    {
        return $this->gmailBodyFromPayload($message['payload'] ?? [], $mimeType);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function gmailBodyFromPayload(array $payload, string $mimeType): ?string
    {
        if (($payload['mimeType'] ?? null) === $mimeType && is_string($payload['body']['data'] ?? null)) {
            return $this->base64UrlDecode($payload['body']['data']);
        }

        foreach ($payload['parts'] ?? [] as $part) {
            if (! is_array($part)) {
                continue;
            }

            $body = $this->gmailBodyFromPayload($part, $mimeType);

            if ($body !== null) {
                return $body;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return array<string, string>
     */
    private function graphHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $header) {
            if (! is_array($header) || ! is_string($header['name'] ?? null) || ! is_string($header['value'] ?? null)) {
                continue;
            }

            $normalized[$this->canonicalHeaderName($header['name'])] = $header['value'];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function graphBody(array $message): ?string
    {
        $body = $message['body']['content'] ?? null;

        return is_string($body) ? $body : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<InboundMailAttachment>
     */
    private function gmailAttachments(MailAccount $account, string $messageId, array $payload): array
    {
        $attachments = [];
        $this->collectGmailAttachments($account, $messageId, $payload, $attachments);

        return $attachments;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<InboundMailAttachment>  $attachments
     */
    private function collectGmailAttachments(MailAccount $account, string $messageId, array $payload, array &$attachments): void
    {
        $filename = $this->decodeHeader(is_string($payload['filename'] ?? null) ? $payload['filename'] : null);
        $attachmentId = $payload['body']['attachmentId'] ?? null;

        if ($filename !== null && trim($filename) !== '' && is_string($attachmentId) && $attachmentId !== '') {
            $attachments[] = new InboundMailAttachment(
                filename: $filename,
                mimeType: is_string($payload['mimeType'] ?? null) ? $payload['mimeType'] : 'application/octet-stream',
                contents: $this->getGmailAttachment($account, $messageId, $attachmentId),
            );
        }

        foreach ($payload['parts'] ?? [] as $part) {
            if (is_array($part)) {
                $this->collectGmailAttachments($account, $messageId, $part, $attachments);
            }
        }
    }

    private function getGmailAttachment(MailAccount $account, string $messageId, string $attachmentId): string
    {
        $response = Http::withToken((string) $account->oauth_access_token)
            ->acceptJson()
            ->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$messageId}/attachments/{$attachmentId}");

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'Gmail API get attachment failed.');
        }

        $data = $response->json('data');

        return is_string($data) ? $this->base64UrlDecode($data) : '';
    }

    /**
     * @return list<InboundMailAttachment>
     */
    private function graphAttachments(MailAccount $account, string $messageId, bool $hasAttachments): array
    {
        if (! $hasAttachments) {
            return [];
        }

        $response = Http::withToken((string) $account->oauth_access_token)
            ->acceptJson()
            ->get("https://graph.microsoft.com/v1.0/me/messages/{$messageId}/attachments", [
                '$select' => 'name,contentType,contentBytes',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($response->body() ?: 'Microsoft Graph list attachments failed.');
        }

        $attachments = [];
        foreach ($response->json('value', []) as $attachment) {
            if (! is_array($attachment) || ($attachment['@odata.type'] ?? null) !== '#microsoft.graph.fileAttachment') {
                continue;
            }

            $name = $this->decodeHeader(is_string($attachment['name'] ?? null) ? $attachment['name'] : null);
            $contentBytes = $attachment['contentBytes'] ?? null;

            if ($name === null || trim($name) === '' || ! is_string($contentBytes)) {
                continue;
            }

            $attachments[] = new InboundMailAttachment(
                filename: $name,
                mimeType: is_string($attachment['contentType'] ?? null) ? $attachment['contentType'] : 'application/octet-stream',
                contents: base64_decode($contentBytes) ?: '',
            );
        }

        return $attachments;
    }

    /**
     * @return array{email: ?string, name: ?string}
     */
    private function parseAddress(string $address): array
    {
        $decoded = $this->decodeHeader($address) ?? '';

        if (preg_match('/^(?:"?([^"<]*)"?)?\s*<([^>]+)>$/', $decoded, $matches) === 1) {
            return [
                'email' => Str::lower(trim($matches[2])),
                'name' => trim($matches[1]) ?: null,
            ];
        }

        if (filter_var(trim($decoded), FILTER_VALIDATE_EMAIL) !== false) {
            return [
                'email' => Str::lower(trim($decoded)),
                'name' => null,
            ];
        }

        return ['email' => null, 'name' => null];
    }

    private function decodeHeader(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($value);
        }

        return $value;
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function canonicalHeaderName(string $name): string
    {
        return collect(explode('-', Str::lower($name)))
            ->map(fn (string $part): string => Str::ucfirst($part))
            ->implode('-');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
