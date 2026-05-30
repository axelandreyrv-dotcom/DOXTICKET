<?php

namespace App\Support\Mail;

use App\Models\Ticket;

readonly class InboundMailResult
{
    public function __construct(
        public string $status,
        public ?Ticket $ticket = null,
        public ?string $reason = null,
    ) {}
}
