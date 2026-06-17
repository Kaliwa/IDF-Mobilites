<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'line_subscription')]
#[ORM\UniqueConstraint(name: 'uniq_user_line_subscription', fields: ['user', 'line'])]
class LineSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: TransitLine::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TransitLine $line = null;

    #[ORM\Column]
    private bool $enabled = true;

    /**
     * @var list<string>
     */
    #[ORM\Column]
    private array $channels = [];

    /**
     * PRIM ItemIdentifier values already notified — prevents duplicate disruption alerts.
     *
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $notifiedDisruptionIds = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLine(): ?TransitLine
    {
        return $this->line;
    }

    public function setLine(TransitLine $line): static
    {
        $this->line = $line;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * @param list<string> $channels
     */
    public function setChannels(array $channels): static
    {
        $this->channels = array_values(array_unique($channels));

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getNotifiedDisruptionIds(): array
    {
        return $this->notifiedDisruptionIds;
    }

    /**
     * @param list<string> $ids
     */
    public function setNotifiedDisruptionIds(array $ids): static
    {
        $this->notifiedDisruptionIds = array_values(array_unique($ids));

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
