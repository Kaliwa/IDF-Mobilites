<?php

namespace App\Entity;

use App\Repository\RecommendationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Recommandation finale d'un parcours : une ou plusieurs offres conseillées
 * accompagnées des aides applicables.
 */
#[ORM\Entity(repositoryClass: RecommendationRepository::class)]
class Recommendation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EventScenario::class, inversedBy: 'recommendations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?EventScenario $scenario = null;

    #[ORM\Column(length: 64)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Offres conseillées : liste d'objets { code, label, description }.
     *
     * @var array<int, array{code: string, label: string, description?: string}>
     */
    #[ORM\Column(type: 'json')]
    private array $offres = [];

    /**
     * Aides applicables : liste d'objets { code, label, description }.
     *
     * @var array<int, array{code: string, label: string, description?: string}>
     */
    #[ORM\Column(type: 'json')]
    private array $aides = [];

    /**
     * Décrit, le cas échéant, la vérification d'éligibilité proposée à l'issue du parcours :
     * { aideCode, label, methodes: ["france_connect","justificatif"] }. Null si aucune vérification.
     *
     * @var array{aideCode: string, label: string, methodes: array<int, string>}|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $verification = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ctaLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ctaUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScenario(): ?EventScenario
    {
        return $this->scenario;
    }

    public function setScenario(?EventScenario $scenario): static
    {
        $this->scenario = $scenario;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return array<int, array{code: string, label: string, description?: string}>
     */
    public function getOffres(): array
    {
        return $this->offres;
    }

    /**
     * @param array<int, array{code: string, label: string, description?: string}> $offres
     */
    public function setOffres(array $offres): static
    {
        $this->offres = $offres;

        return $this;
    }

    /**
     * @return array<int, array{code: string, label: string, description?: string}>
     */
    public function getAides(): array
    {
        return $this->aides;
    }

    /**
     * @param array<int, array{code: string, label: string, description?: string}> $aides
     */
    public function setAides(array $aides): static
    {
        $this->aides = $aides;

        return $this;
    }

    /**
     * @return array{aideCode: string, label: string, methodes: array<int, string>}|null
     */
    public function getVerification(): ?array
    {
        return $this->verification;
    }

    /**
     * @param array{aideCode: string, label: string, methodes: array<int, string>}|null $verification
     */
    public function setVerification(?array $verification): static
    {
        $this->verification = $verification;

        return $this;
    }

    public function getCtaLabel(): ?string
    {
        return $this->ctaLabel;
    }

    public function setCtaLabel(?string $ctaLabel): static
    {
        $this->ctaLabel = $ctaLabel;

        return $this;
    }

    public function getCtaUrl(): ?string
    {
        return $this->ctaUrl;
    }

    public function setCtaUrl(?string $ctaUrl): static
    {
        $this->ctaUrl = $ctaUrl;

        return $this;
    }
}
