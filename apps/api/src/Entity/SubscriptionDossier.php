<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'subscription_dossier')]
class SubscriptionDossier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** subscription_request | renewal | payer_change */
    #[ORM\Column(length: 32)]
    private ?string $type = null;

    /** carte_identite | justificatif_domicile | rib | certificat_scolarite */
    #[ORM\Column(length: 40)]
    private ?string $documentType = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $documentRef = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $ocrData = [];

    #[ORM\Column]
    private float $ocrScore = 0.0;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $ocrFlags = [];

    /** pending | in_review | approved | rejected */
    #[ORM\Column(length: 16)]
    private ?string $status = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $agentNote = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getDocumentType(): ?string { return $this->documentType; }
    public function setDocumentType(string $documentType): static { $this->documentType = $documentType; return $this; }

    public function getDocumentRef(): ?string { return $this->documentRef; }
    public function setDocumentRef(?string $documentRef): static { $this->documentRef = $documentRef; return $this; }

    /** @return array<string, mixed> */
    public function getOcrData(): array { return $this->ocrData; }
    /** @param array<string, mixed> $ocrData */
    public function setOcrData(array $ocrData): static { $this->ocrData = $ocrData; return $this; }

    public function getOcrScore(): float { return $this->ocrScore; }
    public function setOcrScore(float $ocrScore): static { $this->ocrScore = $ocrScore; return $this; }

    /** @return list<string> */
    public function getOcrFlags(): array { return $this->ocrFlags; }
    /** @param list<string> $ocrFlags */
    public function setOcrFlags(array $ocrFlags): static { $this->ocrFlags = array_values($ocrFlags); return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getAgentNote(): ?string { return $this->agentNote; }
    public function setAgentNote(?string $agentNote): static { $this->agentNote = $agentNote; return $this; }

    public function getReviewedBy(): ?User { return $this->reviewedBy; }
    public function setReviewedBy(?User $reviewedBy): static { $this->reviewedBy = $reviewedBy; return $this; }

    public function getReviewedAt(): ?\DateTimeImmutable { return $this->reviewedAt; }
    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static { $this->reviewedAt = $reviewedAt; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
