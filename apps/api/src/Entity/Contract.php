<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contract')]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Porteur (the subscriber who carries the transit pass) */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Payeur::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Payeur $payeur = null;

    #[ORM\ManyToOne(targetEntity: TransitLine::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TransitLine $line = null;

    /** pending | active | suspended | cancelled */
    #[ORM\Column(length: 16)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $suspendedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $suspendedUntil = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $suspensionReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getPayeur(): ?Payeur { return $this->payeur; }
    public function setPayeur(?Payeur $payeur): static { $this->payeur = $payeur; return $this; }

    public function getLine(): ?TransitLine { return $this->line; }
    public function setLine(?TransitLine $line): static { $this->line = $line; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getSuspendedAt(): ?\DateTimeImmutable { return $this->suspendedAt; }
    public function setSuspendedAt(?\DateTimeImmutable $suspendedAt): static { $this->suspendedAt = $suspendedAt; return $this; }

    public function getSuspendedUntil(): ?\DateTimeImmutable { return $this->suspendedUntil; }
    public function setSuspendedUntil(?\DateTimeImmutable $suspendedUntil): static { $this->suspendedUntil = $suspendedUntil; return $this; }

    public function getSuspensionReason(): ?string { return $this->suspensionReason; }
    public function setSuspensionReason(?string $suspensionReason): static { $this->suspensionReason = $suspensionReason; return $this; }

    public function getCancelledAt(): ?\DateTimeImmutable { return $this->cancelledAt; }
    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static { $this->cancelledAt = $cancelledAt; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
