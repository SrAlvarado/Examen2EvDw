<?php

namespace App\DTO\Response;

class MetadataResponse
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $totalItems
    ) {}
}
