<?php

namespace App\DTO\Response;

class ActivityResponse
{
    public function __construct(
        public int $id,
        public int $max_participants,
        public int $clients_signed,
        public string $type,
        public string $date_start,
        public string $date_end,
        public array $play_list = []
    ) {}

    public static function fromEntity(\App\Entity\Activity $activity): self
    {
        $songs = [];
        foreach ($activity->getPlayList() as $song) {
            $songs[] = SongResponse::fromEntity($song);
        }

        return new self(
            id: $activity->getId(),
            max_participants: $activity->getMaxParticipants(),
            clients_signed: $activity->getClientsSigned(),
            type: $activity->getType(),
            date_start: $activity->getDateStart()->format('c'),
            date_end: $activity->getDateEnd()->format('c'),
            play_list: $songs
        );
    }
}
