<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    public const TYPE_STANDARD = 'standard';
    public const TYPE_PREMIUM = 'premium';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['client:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['client:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    #[Groups(['client:read'])]
    private ?string $type = self::TYPE_STANDARD;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    #[Ignore]
    private bool $includeBookings = false;

    #[Ignore]
    private bool $includeStatistics = false;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function isStandardUser(): bool
    {
        return $this->type === self::TYPE_STANDARD;
    }

    public function isPremiumUser(): bool
    {
        return $this->type === self::TYPE_PREMIUM;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setClient($this);
        }
        return $this;
    }

    public function setIncludeBookings(bool $include): static
    {
        $this->includeBookings = $include;
        return $this;
    }

    public function setIncludeStatistics(bool $include): static
    {
        $this->includeStatistics = $include;
        return $this;
    }

    #[Groups(['client:read'])]
    #[SerializedName('activities_booked')]
    public function getActivitiesBooked(): ?array
    {
        if (!$this->includeBookings) {
            return null;
        }

        return $this->buildFutureBookingsArray();
    }

    #[Groups(['client:read'])]
    #[SerializedName('activity_statistics')]
    public function getActivityStatistics(): ?array
    {
        if (!$this->includeStatistics) {
            return null;
        }

        return $this->buildStatisticsByYear();
    }

    private function buildFutureBookingsArray(): array
    {
        $result = [];
        $now = new \DateTime();

        foreach ($this->bookings as $booking) {
            if ($this->isFutureBooking($booking, $now)) {
                $result[] = $this->formatBookingData($booking);
            }
        }

        return $result;
    }

    private function isFutureBooking(Booking $booking, \DateTime $referenceDate): bool
    {
        return $booking->getActivity()->getDateEnd() >= $referenceDate;
    }

    private function formatBookingData(Booking $booking): array
    {
        $activity = $booking->getActivity();

        return [
            'id' => $booking->getId(),
            'activity' => [
                'id' => $activity->getId(),
                'type' => $activity->getType(),
                'max_participants' => $activity->getMaxParticipants(),
                'clients_signed' => $activity->getClientsSigned(),
                'date_start' => $activity->getDateStart()->format('c'),
                'date_end' => $activity->getDateEnd()->format('c'),
            ],
            'client_id' => $this->id,
        ];
    }

    private function buildStatisticsByYear(): array
    {
        $now = new \DateTime();
        $rawStatistics = $this->aggregatePastActivityStatistics($now);

        return $this->formatStatisticsForResponse($rawStatistics);
    }

    private function aggregatePastActivityStatistics(\DateTime $referenceDate): array
    {
        $statisticsByYear = [];

        foreach ($this->bookings as $booking) {
            $activity = $booking->getActivity();

            if ($this->isPastActivity($activity, $referenceDate)) {
                $this->addActivityToStatistics($statisticsByYear, $activity);
            }
        }

        return $statisticsByYear;
    }

    private function isPastActivity(Activity $activity, \DateTime $referenceDate): bool
    {
        return $activity->getDateEnd() < $referenceDate;
    }

    private function addActivityToStatistics(array &$statistics, Activity $activity): void
    {
        $year = (int) $activity->getDateEnd()->format('Y');
        $type = $activity->getType();
        $durationMinutes = $this->calculateActivityDurationMinutes($activity);

        $this->initializeYearAndType($statistics, $year, $type);

        $statistics[$year][$type]['num_activities']++;
        $statistics[$year][$type]['num_minutes'] += $durationMinutes;
    }

    private function calculateActivityDurationMinutes(Activity $activity): int
    {
        $startTimestamp = $activity->getDateStart()->getTimestamp();
        $endTimestamp = $activity->getDateEnd()->getTimestamp();

        return (int) (($endTimestamp - $startTimestamp) / 60);
    }

    private function initializeYearAndType(array &$statistics, int $year, string $type): void
    {
        if (!isset($statistics[$year])) {
            $statistics[$year] = [];
        }

        if (!isset($statistics[$year][$type])) {
            $statistics[$year][$type] = [
                'num_activities' => 0,
                'num_minutes' => 0,
            ];
        }
    }

    private function formatStatisticsForResponse(array $rawStatistics): array
    {
        $result = [];

        foreach ($rawStatistics as $year => $types) {
            $result[] = [
                'year' => $year,
                'statistics_by_type' => $this->formatTypeStatistics($types),
            ];
        }

        return $result;
    }

    private function formatTypeStatistics(array $types): array
    {
        $formatted = [];

        foreach ($types as $type => $stats) {
            $formatted[] = [
                'type' => $type,
                'statistics' => $stats,
            ];
        }

        return $formatted;
    }
}
