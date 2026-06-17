<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payment')]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Abonnement::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Abonnement $abonnement = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private ?string $amount = null;

    /** paid | failed | pending */
    #[ORM\Column(length: 16)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $failureNotifiedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getAbonnement(): ?Abonnement { return $this->abonnement; }
    public function setAbonnement(?Abonnement $abonnement): static { $this->abonnement = $abonnement; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getProcessedAt(): ?\DateTimeImmutable { return $this->processedAt; }
    public function setProcessedAt(\DateTimeImmutable $processedAt): static { $this->processedAt = $processedAt; return $this; }

    public function getFailureNotifiedAt(): ?\DateTimeImmutable { return $this->failureNotifiedAt; }
    public function setFailureNotifiedAt(?\DateTimeImmutable $failureNotifiedAt): static { $this->failureNotifiedAt = $failureNotifiedAt; return $this; }
}
