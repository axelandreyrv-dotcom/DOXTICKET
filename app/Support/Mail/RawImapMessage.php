<?php

namespace App\Support\Mail;

readonly class RawImapMessage
{
    /**
     * @param  list<RawImapAttachment>  $attachments
     */
    public function __construct(
        public string $uid,
        public string $headers,
        public ?string $bodyText,
        public ?string $bodyHtml,
        public array $attachments = [],
    ) {}
}
