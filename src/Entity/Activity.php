<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{
    public const TYPE_BODYPUMP = 'BodyPump';
    public const TYPE_SPINNING = 'Spinning';
    public const TYPE_CORE = 'Core';

    public const VALID_TYPES = [
        self::TYPE_BODYPUMP,
        self::TYPE_SPINNING,
        self::TYPE_CORE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['activity:read', 'booking:read', 'client:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['activity:read', 'booking:read'])]
    #[SerializedName('max_participants')]
    private ?int $maxParticipants = null;

    #[ORM\Column(length: 50)]
    #[Groups(['activity:read', 'booking:read'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['activity:read', 'booking:read'])]
    #[SerializedName('date_start')]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['activity:read', 'booking:read'])]
    #[SerializedName('date_end')]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: Song::class, cascade: ['persist', 'remove'])]
    #[Groups(['activity:read'])]
    #[SerializedName('play_list')]
    private Collection $playList;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    public function __construct()
    {
        $this->playList = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
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

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): static
    {
        $this->dateStart = $dateStart;
        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTimeInterface $dateEnd): static
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getPlayList(): Collection
    {
        return $this->playList;
    }

    public function addSong(Song $song): static
    {
        if (!$this->playList->contains($song)) {
            $this->playList->add($song);
            $song->setActivity($this);
        }
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
            $booking->setActivity($this);
        }
        return $this;
    }

    #[Groups(['activity:read'])]
    #[SerializedName('clients_signed')]
    public function getClientsSigned(): int
    {
        return $this->bookings->count();
    }

    public function hasFreePlaces(): bool
    {
        return $this->getClientsSigned() < $this->maxParticipants;
    }

    public function getAvailablePlaces(): int
    {
        return $this->maxParticipants - $this->getClientsSigned();
    }

    public function isFull(): bool
    {
        return !$this->hasFreePlaces();
    }

    public function isClientAlreadyBooked(Client $client): bool
    {
        foreach ($this->bookings as $booking) {
            if ($booking->getClient()->getId() === $client->getId()) {
                return true;
            }
        }
        return false;
    }
}
