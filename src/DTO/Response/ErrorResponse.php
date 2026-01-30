<?php

namespace App\DTO\Response;

class ErrorResponse
{
    public function __construct(
        public int $code,
        public string $description
    ) {}

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'description' => $this->description,
        ];
    }
}
