<?php

namespace App\Support\Mail;

readonly class MailAccountTestResult
{
    private function __construct(
        public bool $ok,
        public ?string $message = null,
    ) {}

    public static function ok(): self
    {
        return new self(true);
    }

    public static function failed(string $message): self
    {
        return new self(false, $message);
    }
}
