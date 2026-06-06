<?php

namespace App\Services\Mail;

use App\Contracts\Mail\ImapConnection;
use App\Contracts\Mail\MailboxClient;
use App\Models\MailAccount;
use App\Support\Mail\FetchedMailMessage;
use App\Support\Mail\InboundMailAttachment;
use App\Support\Mail\InboundMailMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class ImapMailboxClient implements MailboxClient
{
    public function __construct(
        private readonly ImapConnection $connection,
    ) {}

    public function fetchNewMessages(MailAccount $account): iterable
    {
        foreach ($this->connection->fetchNewMessages($account) as $raw) {
            $headers = $this->parseHeaders($raw->headers);
            $from = $this->parseAddress($headers['From'] ?? '');

            if ($from['email'] === null) {
                continue;
            }

            yield new FetchedMailMessage($raw->uid, new InboundMailMessage(
                messageId: $headers['Message-ID'] ?? $headers['Message-Id'] ?? null,
                fromEmail: $from['email'],
                fromName: $from['name'],
                subject: $this->decodeHeader($headers['Subject'] ?? null),
                bodyText: $raw->bodyText,
                bodyHtml: $raw->bodyHtml,
                headers: $headers,
                inReplyTo: $headers['In-Reply-To'] ?? null,
                references: $headers['References'] ?? null,
                deliveredAt: $this->parseDate($headers['Date'] ?? null),
                attachments: $this->attachments($raw->attachments),
            ));
        }
    }

    /**
     * @return list<InboundMailAttachment>
     */
    private function attachments(array $attachments): array
    {
        return array_map(
            fn ($attachment): InboundMailAttachment => new InboundMailAttachment(
                filename: $this->decodeHeader($attachment->filename) ?? $attachment->filename,
                mimeType: $attachment->mimeType,
                contents: $attachment->contents,
            ),
            $attachments,
        );
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $current = null;

        foreach (preg_split('/\r\n|\n|\r/', $rawHeaders) ?: [] as $line) {
            if ($line === '') {
                continue;
            }

            if (($line[0] === ' ' || $line[0] === "\t") && $current !== null) {
                $headers[$current] .= ' '.trim($line);

                continue;
            }

            [$name, $value] = array_pad(explode(':', $line, 2), 2, '');
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $current = $this->canonicalHeaderName($name);
            $headers[$current] = trim($value);
        }

        return $headers;
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
}
