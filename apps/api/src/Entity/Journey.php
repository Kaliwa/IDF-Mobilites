<?php

namespace App\Entity;

use App\Repository\JourneyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JourneyRepository::class)]
#[ORM\Table(name: 'journey')]
#[ORM\HasLifecycleCallbacks]
class Journey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $label = 'Trajet principal';

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $originName = '';

    #[ORM\Column(type: Types::FLOAT)]
    private float $originLat = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private float $originLng = 0.0;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $destinationName = '';

    #[ORM\Column(type: Types::FLOAT)]
    private float $destinationLat = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private float $destinationLng = 0.0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $lines = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = trim($label) ?: 'Trajet principal';
        return $this;
    }

    public function getOriginName(): string
    {
        return $this->originName;
    }

    public function setOriginName(string $originName): self
    {
        $this->originName = $originName;
        return $this;
    }

    public function getOriginLat(): float
    {
        return $this->originLat;
    }

    public function setOriginLat(float $originLat): self
    {
        $this->originLat = $originLat;
        return $this;
    }

    public function getOriginLng(): float
    {
        return $this->originLng;
    }

    public function setOriginLng(float $originLng): self
    {
        $this->originLng = $originLng;
        return $this;
    }

    public function getDestinationName(): string
    {
        return $this->destinationName;
    }

    public function setDestinationName(string $destinationName): self
    {
        $this->destinationName = $destinationName;
        return $this;
    }

    public function getDestinationLat(): float
    {
        return $this->destinationLat;
    }

    public function setDestinationLat(float $destinationLat): self
    {
        $this->destinationLat = $destinationLat;
        return $this;
    }

    public function getDestinationLng(): float
    {
        return $this->destinationLng;
    }

    public function setDestinationLng(float $destinationLng): self
    {
        $this->destinationLng = $destinationLng;
        return $this;
    }

    public function getLines(): ?array
    {
        return $this->lines;
    }

    public function setLines(?array $lines): self
    {
        $this->lines = $lines;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

