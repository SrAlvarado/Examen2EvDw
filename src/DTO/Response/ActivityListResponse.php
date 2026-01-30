<?php

namespace App\DTO\Response;

class ActivityListResponse
{
    public function __construct(
        public array $data,
        public MetadataResponse $meta
    ) {}

    public static function fromEntities(array $activities, int $page, int $limit, int $totalItems): self
    {
        $data = array_map(
            fn($activity) => ActivityResponse::fromEntity($activity),
            $activities
        );

        return new self(
            data: $data,
            meta: new MetadataResponse($page, $limit, $totalItems)
        );
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(fn($item) => (array) $item, $this->data),
            'meta' => [
                'page' => $this->meta->page,
                'limit' => $this->meta->limit,
                'total-items' => $this->meta->totalItems,
            ],
        ];
    }
}
