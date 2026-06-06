<?php

namespace App\Services\Tickets;

use App\Models\Attachment;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketAttachmentService
{
    private const BLOCKED_EXTENSIONS = ['bat', 'cmd', 'com', 'exe', 'js', 'jse', 'lnk', 'msi', 'ps1', 'scr', 'sh', 'vbs', 'wsf'];

    public function storeContent(
        Ticket $ticket,
        ?Membership $actor,
        ?TicketMessage $message,
        string $filename,
        string $mimeType,
        string $contents,
    ): ?Attachment {
        $filename = $this->safeFilename($filename);
        $mimeType = $mimeType ?: 'application/octet-stream';
        $sizeBytes = strlen($contents);

        if ($sizeBytes > $this->maxBytes()) {
            $this->recordBlockedEvent($ticket, $actor, $filename, $mimeType, 'file_too_large', [
                'size_bytes' => $sizeBytes,
                'max_size_bytes' => $this->maxBytes(),
            ]);

            return null;
        }

        if ($this->isBlocked($filename, $mimeType)) {
            $this->recordBlockedEvent($ticket, $actor, $filename, $mimeType, 'blocked_file_type');

            return null;
        }

        $uuid = (string) Str::uuid();
        $path = 'attachments/'.$ticket->company_id.'/'.$ticket->id.'/'.$uuid.'/'.$filename;

        Storage::disk('private')->put($path, $contents);

        $attachment = Attachment::withoutTenant()->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message?->id,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'disk' => 'private',
            'path' => $path,
            'checksum_sha256' => hash('sha256', $contents),
        ]);

        TicketEvent::withoutTenant()->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'actor_user_id' => $actor?->user_id,
            'actor_membership_id' => $actor?->id,
            'type' => 'ticket.attachment_added',
            'payload' => [
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size_bytes' => $sizeBytes,
            ],
        ]);

        return $attachment;
    }

    public function isBlocked(string $filename, string $mimeType): bool
    {
        $extension = Str::lower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return true;
        }

        return str_contains(Str::lower($mimeType), 'x-msdownload')
            || str_contains(Str::lower($mimeType), 'x-sh');
    }

    public function safeFilename(string $filename): string
    {
        $name = basename($filename);
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?: 'attachment';
        $name = trim($name, '. ');

        return $name === '' ? 'attachment' : Str::limit($name, 180, '');
    }

    public function maxBytes(): int
    {
        return max(1, (int) config('doxticket.attachments.max_bytes', 10 * 1024 * 1024));
    }

    /**
     * @param  array<string, mixed>  $extraPayload
     */
    public function recordBlocked(Ticket $ticket, ?Membership $actor, string $filename, string $mimeType, string $reason, array $extraPayload = []): void
    {
        $this->recordBlockedEvent($ticket, $actor, $this->safeFilename($filename), $mimeType, $reason, $extraPayload);
    }

    /**
     * @param  array<string, mixed>  $extraPayload
     */
    private function recordBlockedEvent(Ticket $ticket, ?Membership $actor, string $filename, string $mimeType, string $reason, array $extraPayload = []): void
    {
        TicketEvent::withoutTenant()->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'actor_user_id' => $actor?->user_id,
            'actor_membership_id' => $actor?->id,
            'type' => 'ticket.attachment_blocked',
            'payload' => [
                'filename' => $filename,
                'mime_type' => $mimeType,
                'reason' => $reason,
            ] + $extraPayload,
        ]);
    }
}
