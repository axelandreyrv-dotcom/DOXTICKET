<?php

namespace App\Support\Mail;

readonly class RawImapAttachment
{
    public function __construct(
        public string $filename,
        public string $mimeType,
        public string $contents,
    ) {}
}
