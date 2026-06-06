<?php

namespace App\Support\Mail;

readonly class InboundMailAttachment
{
    public function __construct(
        public string $filename,
        public string $mimeType,
        public string $contents,
    ) {}
}
