<?php

namespace App\Services\Admin;

class HealthCheckResult
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $status,
        public readonly string $message,
    ) {}
}
