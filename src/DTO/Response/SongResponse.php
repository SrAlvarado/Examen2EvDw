<?php

namespace App\DTO\Response;

class SongResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $duration_seconds
    ) {}

    public static function fromEntity(\App\Entity\Song $song): self
    {
        return new self(
            id: $song->getId(),
            name: $song->getName(),
            duration_seconds: $song->getDurationSeconds()
        );
    }
}
