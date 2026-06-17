<?php

namespace App\Entity;

use App\Enum\Periodicite;
use App\Enum\StatutAbonnement;
use App\Repository\AbonnementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Souscription d'une offre par un bénéficiaire, réglée par un payeur éventuel.
 */
#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Type d'offre souscrite (ex. « navigo_imagine_r », « navigo_liberte_plus », « forfait_senior »).
     */
    #[ORM\Column(length: 64)]
    private ?string $typeOffre = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(length: 16, enumType: StatutAbonnement::class)]
    private StatutAbonnement $statut = StatutAbonnement::EN_ATTENTE;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 16, enumType: Periodicite::class)]
    private ?Periodicite $periodicite = null;

    /**
     * Bénéficiaire de l'abonnement. onDelete CASCADE : la suppression du compte
     * supprime ses abonnements (données rattachées à la personne).
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'abonnements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $beneficiaire = null;

    /**
     * Payeur de l'abonnement. nullable : le bénéficiaire peut se payer lui-même.
     * onDelete SET NULL : supprimer un payeur ne supprime pas l'abonnement.
     */
    #[ORM\ManyToOne(targetEntity: Payeur::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Payeur $payeur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeOffre(): ?string
    {
        return $this->typeOffre;
    }

    public function setTypeOffre(string $typeOffre): static
    {
        $this->typeOffre = $typeOffre;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getStatut(): StatutAbonnement
    {
        return $this->statut;
    }

    public function setStatut(StatutAbonnement $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getPeriodicite(): ?Periodicite
    {
        return $this->periodicite;
    }

    public function setPeriodicite(Periodicite $periodicite): static
    {
        $this->periodicite = $periodicite;

        return $this;
    }

    public function getBeneficiaire(): ?User
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?User $beneficiaire): static
    {
        $this->beneficiaire = $beneficiaire;

        return $this;
    }

    public function getPayeur(): ?Payeur
    {
        return $this->payeur;
    }

    public function setPayeur(?Payeur $payeur): static
    {
        $this->payeur = $payeur;

        return $this;
    }
}
