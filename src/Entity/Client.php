<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
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
    private ?string $type = 'standard';

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    // Flag to include bookings in serialization
    #[Ignore]
    private bool $includeBookings = false;

    // Flag to include statistics in serialization
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

        $result = [];
        $now = new \DateTime();
        foreach ($this->bookings as $booking) {
            // Only include future/current bookings
            if ($booking->getActivity()->getDateEnd() >= $now) {
                $result[] = [
                    'id' => $booking->getId(),
                    'activity' => [
                        'id' => $booking->getActivity()->getId(),
                        'type' => $booking->getActivity()->getType(),
                        'max_participants' => $booking->getActivity()->getMaxParticipants(),
                        'clients_signed' => $booking->getActivity()->getClientsSigned(),
                        'date_start' => $booking->getActivity()->getDateStart()->format('c'),
                        'date_end' => $booking->getActivity()->getDateEnd()->format('c'),
                    ],
                    'client_id' => $this->id,
                ];
            }
        }
        return $result;
    }

    #[Groups(['client:read'])]
    #[SerializedName('activity_statistics')]
    public function getActivityStatistics(): ?array
    {
        if (!$this->includeStatistics) {
            return null;
        }

        $now = new \DateTime();
        $statisticsByYear = [];

        foreach ($this->bookings as $booking) {
            $activity = $booking->getActivity();
            // Only include past activities
            if ($activity->getDateEnd() < $now) {
                $year = (int) $activity->getDateEnd()->format('Y');
                $type = $activity->getType();
                
                // Calculate duration in minutes
                $duration = ($activity->getDateEnd()->getTimestamp() - $activity->getDateStart()->getTimestamp()) / 60;

                if (!isset($statisticsByYear[$year])) {
                    $statisticsByYear[$year] = [];
                }
                if (!isset($statisticsByYear[$year][$type])) {
                    $statisticsByYear[$year][$type] = [
                        'num_activities' => 0,
                        'num_minutes' => 0,
                    ];
                }

                $statisticsByYear[$year][$type]['num_activities']++;
                $statisticsByYear[$year][$type]['num_minutes'] += (int) $duration;
            }
        }

        // Format the result according to OpenAPI spec
        $result = [];
        foreach ($statisticsByYear as $year => $types) {
            $statisticsByType = [];
            foreach ($types as $type => $stats) {
                $statisticsByType[] = [
                    'type' => $type,
                    'statistics' => $stats,
                ];
            }
            $result[] = [
                'year' => $year,
                'statistics_by_type' => $statisticsByType,
            ];
        }

        return $result;
    }
}
