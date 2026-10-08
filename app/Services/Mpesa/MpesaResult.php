<?php

namespace App\Services\Mpesa;

/** One response from the M-Pesa OpenAPI. */
final readonly class MpesaResult
{
    public function __construct(
        public int $status,
        public ?string $code,
        public ?string $description,
        public array $body,
    ) {}

    public function successful(): bool
    {
        return $this->code === 'INS-0';
    }

    public function get(string $field): ?string
    {
        $value = $this->body[$field] ?? null;

        return $value === null ? null : (string) $value;
    }
}
