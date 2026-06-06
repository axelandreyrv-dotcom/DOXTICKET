<?php

namespace App\Support\Mail;

use Carbon\CarbonInterface;

readonly class InboundMailMessage
{
    /**
     * @param  array<string, string>  $headers
     * @param  list<InboundMailAttachment>  $attachments
     */
    public function __construct(
        public ?string $messageId,
        public string $fromEmail,
        public ?string $fromName,
        public ?string $subject,
        public ?string $bodyText,
        public ?string $bodyHtml,
        public array $headers = [],
        public ?string $inReplyTo = null,
        public ?string $references = null,
        public ?CarbonInterface $deliveredAt = null,
        public array $attachments = [],
    ) {}

    public function normalizedSubject(): string
    {
        $subject = trim((string) $this->subject);

        return $subject === '' ? 'Sin Asunto' : $subject;
    }
}
