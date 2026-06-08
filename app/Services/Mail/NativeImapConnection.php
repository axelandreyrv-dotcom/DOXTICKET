<?php

namespace App\Services\Mail;

use App\Contracts\Mail\ImapConnection;
use App\Models\MailAccount;
use App\Support\Mail\RawImapAttachment;
use App\Support\Mail\RawImapMessage;
use RuntimeException;

class NativeImapConnection implements ImapConnection
{
    public function fetchNewMessages(MailAccount $account): iterable
    {
        if (! function_exists('imap_open')) {
            throw new RuntimeException('La extensión PHP IMAP no está instalada. Habilítala en PHP CLI/FPM y reinicia los workers.');
        }

        $connection = @imap_open($this->mailboxName($account), $account->username, $account->password_encrypted);

        if ($connection === false) {
            throw new RuntimeException('IMAP connection failed: '.$this->lastError());
        }

        try {
            $uids = imap_search($connection, $this->searchCriteria($account), SE_UID) ?: [];
            sort($uids, SORT_NUMERIC);

            foreach ($uids as $uid) {
                $uid = (int) $uid;

                if (! $this->shouldFetchUid($account, $uid)) {
                    continue;
                }

                $headers = imap_fetchheader($connection, $uid, FT_UID) ?: '';
                $messageParts = $this->messageParts($connection, $uid);

                yield new RawImapMessage(
                    uid: (string) $uid,
                    headers: $headers,
                    bodyText: $messageParts['text'],
                    bodyHtml: $messageParts['html'],
                    attachments: $messageParts['attachments'],
                );
            }
        } finally {
            imap_close($connection);
        }
    }

    private function mailboxName(MailAccount $account): string
    {
        $security = match ($account->security_imap) {
            'ssl' => '/ssl',
            'tls' => '/tls',
            default => '/notls',
        };

        if (! (bool) config('doxticket.mail.imap_validate_cert', true)) {
            $security .= '/novalidate-cert';
        }

        $folder = trim((string) ($account->folder_in ?: 'INBOX')) ?: 'INBOX';

        return sprintf('{%s:%d/imap%s}%s', $account->host_imap, $account->port_imap, $security, $folder);
    }

    private function searchCriteria(MailAccount $account): string
    {
        return 'ALL';
    }

    private function shouldFetchUid(MailAccount $account, int $uid): bool
    {
        if (! ctype_digit((string) $account->last_uid) || (int) $account->last_uid <= 0) {
            return true;
        }

        return $uid > (int) $account->last_uid;
    }

    /**
     * @return array{text: ?string, html: ?string, attachments: list<RawImapAttachment>}
     */
    private function messageParts(mixed $connection, int $uid): array
    {
        $structure = imap_fetchstructure($connection, $uid, FT_UID);
        $parts = ['text' => null, 'html' => null, 'attachments' => []];

        if ($structure === false) {
            return $parts;
        }

        $this->collectParts($connection, $uid, $structure, '', $parts);

        return $parts;
    }

    /**
     * @param  array{text: ?string, html: ?string, attachments: list<RawImapAttachment>}  $parts
     */
    private function collectParts(mixed $connection, int $uid, object $structure, string $partNumber, array &$parts): void
    {
        if (isset($structure->parts) && is_array($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $nextPartNumber = $partNumber === '' ? (string) ($index + 1) : $partNumber.'.'.($index + 1);
                $this->collectParts($connection, $uid, $part, $nextPartNumber, $parts);
            }

            return;
        }

        $body = imap_fetchbody($connection, $uid, $partNumber === '' ? '1' : $partNumber, FT_UID | FT_PEEK);

        if ($body === false) {
            return;
        }

        $decoded = $this->decodeBody($body, (int) ($structure->encoding ?? ENC7BIT));
        $filename = $this->filename($structure);

        if ($filename !== null) {
            $parts['attachments'][] = new RawImapAttachment(
                filename: $filename,
                mimeType: $this->mimeType($structure),
                contents: $decoded,
            );

            return;
        }

        if (($structure->type ?? null) !== TYPETEXT) {
            return;
        }

        $subtype = strtolower((string) ($structure->subtype ?? 'plain'));

        if ($subtype === 'html' && $parts['html'] === null) {
            $parts['html'] = $decoded;
        }

        if ($subtype === 'plain' && $parts['text'] === null) {
            $parts['text'] = $decoded;
        }
    }

    private function decodeBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            ENCBASE64 => base64_decode($body, true) ?: '',
            ENCQUOTEDPRINTABLE => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function lastError(): string
    {
        return imap_last_error() ?: 'unknown IMAP error';
    }

    private function filename(object $structure): ?string
    {
        foreach (['dparameters', 'parameters'] as $property) {
            foreach (($structure->{$property} ?? []) as $parameter) {
                $attribute = strtolower((string) ($parameter->attribute ?? ''));

                if (in_array($attribute, ['filename', 'name'], true)) {
                    return (string) $parameter->value;
                }
            }
        }

        return null;
    }

    private function mimeType(object $structure): string
    {
        $types = [
            TYPETEXT => 'text',
            TYPEMULTIPART => 'multipart',
            TYPEMESSAGE => 'message',
            TYPEAPPLICATION => 'application',
            TYPEAUDIO => 'audio',
            TYPEIMAGE => 'image',
            TYPEVIDEO => 'video',
            TYPEMODEL => 'model',
        ];

        $type = $types[(int) ($structure->type ?? TYPEAPPLICATION)] ?? 'application';
        $subtype = strtolower((string) ($structure->subtype ?? 'octet-stream'));

        return $type.'/'.$subtype;
    }
}
