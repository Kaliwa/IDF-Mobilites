<?php

namespace App\Entity;

use App\Enum\MethodeVerification;
use App\Enum\StatutVerification;
use App\Repository\EligibilityCheckRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace d'une vérification d'éligibilité à une aide.
 * Peut être anonyme (parcours pré-inscription) : le lien vers User est facultatif.
 */
#[ORM\Entity(repositoryClass: EligibilityCheckRepository::class)]
class EligibilityCheck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code de l'aide vérifiée (ex. « solidarite_transport », « aide_bourse »).
     */
    #[ORM\Column(length: 64)]
    private ?string $aideCode = null;

    #[ORM\Column(length: 16, enumType: MethodeVerification::class)]
    private ?MethodeVerification $methode = null;

    #[ORM\Column(length: 16, enumType: StatutVerification::class)]
    private StatutVerification $statut = StatutVerification::EN_ATTENTE;

    /**
     * Source de la vérification (ex. « API Particulier · CAF », « Justificatif · 2D-Doc »).
     */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $source = null;

    /**
     * Nom du fichier déposé, le cas échéant.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentNom = null;

    /**
     * Données certifiées éventuellement renvoyées par la source (ex. quotient familial).
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $donnees = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAideCode(): ?string
    {
        return $this->aideCode;
    }

    public function setAideCode(string $aideCode): static
    {
        $this->aideCode = $aideCode;

        return $this;
    }

    public function getMethode(): ?MethodeVerification
    {
        return $this->methode;
    }

    public function setMethode(MethodeVerification $methode): static
    {
        $this->methode = $methode;

        return $this;
    }

    public function getStatut(): StatutVerification
    {
        return $this->statut;
    }

    public function setStatut(StatutVerification $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getDocumentNom(): ?string
    {
        return $this->documentNom;
    }

    public function setDocumentNom(?string $documentNom): static
    {
        $this->documentNom = $documentNom;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDonnees(): ?array
    {
        return $this->donnees;
    }

    /**
     * @param array<string, mixed>|null $donnees
     */
    public function setDonnees(?array $donnees): static
    {
        $this->donnees = $donnees;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
