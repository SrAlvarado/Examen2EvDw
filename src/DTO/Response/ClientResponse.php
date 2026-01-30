<?php

namespace App\DTO\Response;

class ClientResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $type,
        public ?array $activities_booked = null,
        public ?array $activity_statistics = null
    ) {}

    public static function fromEntity(
        \App\Entity\Client $client,
        bool $includeBookings = false,
        bool $includeStatistics = false
    ): self {
        $bookings = null;
        $statistics = null;

        if ($includeBookings) {
            $bookings = self::buildBookingsArray($client);
        }

        if ($includeStatistics) {
            $statistics = self::buildStatisticsArray($client);
        }

        return new self(
            id: $client->getId(),
            name: $client->getName(),
            email: $client->getEmail(),
            type: $client->getType(),
            activities_booked: $bookings,
            activity_statistics: $statistics
        );
    }

    private static function buildBookingsArray(\App\Entity\Client $client): array
    {
        $result = [];
        $now = new \DateTime();

        foreach ($client->getBookings() as $booking) {
            $activity = $booking->getActivity();
            if ($activity->getDateEnd() >= $now) {
                $result[] = [
                    'id' => $booking->getId(),
                    'activity' => [
                        'id' => $activity->getId(),
                        'type' => $activity->getType(),
                        'max_participants' => $activity->getMaxParticipants(),
                        'clients_signed' => $activity->getClientsSigned(),
                        'date_start' => $activity->getDateStart()->format('c'),
                        'date_end' => $activity->getDateEnd()->format('c'),
                    ],
                    'client_id' => $client->getId(),
                ];
            }
        }

        return $result;
    }

    private static function buildStatisticsArray(\App\Entity\Client $client): array
    {
        $now = new \DateTime();
        $statisticsByYear = [];

        foreach ($client->getBookings() as $booking) {
            $activity = $booking->getActivity();
            if ($activity->getDateEnd() < $now) {
                $year = (int) $activity->getDateEnd()->format('Y');
                $type = $activity->getType();
                $duration = (int) (($activity->getDateEnd()->getTimestamp() - $activity->getDateStart()->getTimestamp()) / 60);

                if (!isset($statisticsByYear[$year])) {
                    $statisticsByYear[$year] = [];
                }
                if (!isset($statisticsByYear[$year][$type])) {
                    $statisticsByYear[$year][$type] = ['num_activities' => 0, 'num_minutes' => 0];
                }

                $statisticsByYear[$year][$type]['num_activities']++;
                $statisticsByYear[$year][$type]['num_minutes'] += $duration;
            }
        }

        $result = [];
        foreach ($statisticsByYear as $year => $types) {
            $statisticsByType = [];
            foreach ($types as $type => $stats) {
                $statisticsByType[] = ['type' => $type, 'statistics' => $stats];
            }
            $result[] = ['year' => $year, 'statistics_by_type' => $statisticsByType];
        }

        return $result;
    }
}
