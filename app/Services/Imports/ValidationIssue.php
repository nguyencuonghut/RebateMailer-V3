<?php

namespace App\Services\Imports;

readonly class ValidationIssue
{
    public function __construct(
        public string $code,
        public string $message,
        public bool $isBlocking,
    ) {
    }

    /**
     * @return array{code: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
