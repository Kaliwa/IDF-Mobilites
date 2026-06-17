<?php

namespace App\Entity;

use App\Repository\EventScenarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Événement de vie déclenchant un parcours d'orientation
 * (ex. « Je deviens étudiant », « Je pars à la retraite »).
 */
#[ORM\Entity(repositoryClass: EventScenarioRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SCENARIO_CODE', fields: ['code'])]
class EventScenario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Identifiant stable utilisé par le front et l'API (ex. « devenir_etudiant »).
     */
    #[ORM\Column(length: 64)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $label = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Nom de l'icône à afficher côté front (réutilise la librairie d'icônes existante).
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $icone = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $ordre = 0;

    /**
     * Première question posée lorsqu'on entre dans ce parcours.
     */
    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Question $questionInitiale = null;

    /**
     * @var Collection<int, Question>
     */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'scenario', cascade: ['persist'])]
    private Collection $questions;

    /**
     * @var Collection<int, Recommendation>
     */
    #[ORM\OneToMany(targetEntity: Recommendation::class, mappedBy: 'scenario', cascade: ['persist'])]
    private Collection $recommendations;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->recommendations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): static
    {
        $this->icone = $icone;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getQuestionInitiale(): ?Question
    {
        return $this->questionInitiale;
    }

    public function setQuestionInitiale(?Question $questionInitiale): static
    {
        $this->questionInitiale = $questionInitiale;

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setScenario($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Recommendation>
     */
    public function getRecommendations(): Collection
    {
        return $this->recommendations;
    }

    public function addRecommendation(Recommendation $recommendation): static
    {
        if (!$this->recommendations->contains($recommendation)) {
            $this->recommendations->add($recommendation);
            $recommendation->setScenario($this);
        }

        return $this;
    }
}
