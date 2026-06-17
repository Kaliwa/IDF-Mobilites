<?php

namespace App\Entity;

use App\Enum\LienBeneficiaire;
use App\Enum\MoyenPaiement;
use App\Repository\PayeurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Personne qui règle l'abonnement, potentiellement distincte du bénéficiaire
 * (ex. un parent qui paie l'Imagine R de son enfant).
 *
 * Choix de modélisation : lorsqu'un utilisateur se paie lui-même, son champ
 * User::$payeur reste null plutôt que de créer un Payeur dupliquant ses données.
 * Cela évite toute redondance et garde le cas « tiers payeur » explicite.
 */
#[ORM\Entity(repositoryClass: PayeurRepository::class)]
class Payeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 32, enumType: LienBeneficiaire::class)]
    private ?LienBeneficiaire $lienBeneficiaire = null;

    #[ORM\Column(length: 32, enumType: MoyenPaiement::class)]
    private ?MoyenPaiement $moyenPaiement = null;

    /**
     * Bénéficiaires dont ce payeur règle les abonnements (un payeur peut payer pour plusieurs personnes).
     *
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'payeur')]
    private Collection $beneficiaires;

    public function __construct()
    {
        $this->beneficiaires = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->prenom ?? '', $this->nom ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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

    public function getLienBeneficiaire(): ?LienBeneficiaire
    {
        return $this->lienBeneficiaire;
    }

    public function setLienBeneficiaire(LienBeneficiaire $lienBeneficiaire): static
    {
        $this->lienBeneficiaire = $lienBeneficiaire;

        return $this;
    }

    public function getMoyenPaiement(): ?MoyenPaiement
    {
        return $this->moyenPaiement;
    }

    public function setMoyenPaiement(MoyenPaiement $moyenPaiement): static
    {
        $this->moyenPaiement = $moyenPaiement;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getBeneficiaires(): Collection
    {
        return $this->beneficiaires;
    }

    public function addBeneficiaire(User $beneficiaire): static
    {
        if (!$this->beneficiaires->contains($beneficiaire)) {
            $this->beneficiaires->add($beneficiaire);
            $beneficiaire->setPayeur($this);
        }

        return $this;
    }

    public function removeBeneficiaire(User $beneficiaire): static
    {
        if ($this->beneficiaires->removeElement($beneficiaire)) {
            if ($beneficiaire->getPayeur() === $this) {
                $beneficiaire->setPayeur(null);
            }
        }

        return $this;
    }
}
