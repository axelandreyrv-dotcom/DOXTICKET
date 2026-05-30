<?php

namespace App\Support\Mail;

readonly class FetchedMailMessage
{
    public function __construct(
        public string $uid,
        public InboundMailMessage $message,
    ) {}
}
