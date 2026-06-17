<?php

namespace App\Entity;

use App\Enum\TypeQuestion;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Question d'un parcours d'orientation.
 *
 * Transitions :
 *  - pour une question à choix unique, la transition est portée par la réponse choisie (Answer) ;
 *  - pour une question à choix multiple, la transition (questionSuivante / recommendation) est
 *    portée par la question elle-même, les réponses servant alors uniquement de profil.
 */
#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_QUESTION_SCENARIO_CODE', fields: ['scenario', 'code'])]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EventScenario::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?EventScenario $scenario = null;

    /**
     * Identifiant stable de la question au sein du scénario (ex. « age »).
     */
    #[ORM\Column(length: 64)]
    private ?string $code = null;

    #[ORM\Column(type: 'text')]
    private ?string $libelle = null;

    #[ORM\Column(length: 16, enumType: TypeQuestion::class)]
    private TypeQuestion $type = TypeQuestion::CHOIX_UNIQUE;

    #[ORM\Column(options: ['default' => 0])]
    private int $ordre = 0;

    /**
     * Transition utilisée pour les questions à choix multiple (cf. doc de classe).
     */
    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Question $questionSuivante = null;

    #[ORM\ManyToOne(targetEntity: Recommendation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Recommendation $recommendation = null;

    /**
     * @var Collection<int, Answer>
     */
    #[ORM\OneToMany(targetEntity: Answer::class, mappedBy: 'question', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $answers;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getType(): TypeQuestion
    {
        return $this->type;
    }

    public function setType(TypeQuestion $type): static
    {
        $this->type = $type;

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

    public function getQuestionSuivante(): ?Question
    {
        return $this->questionSuivante;
    }

    public function setQuestionSuivante(?Question $questionSuivante): static
    {
        $this->questionSuivante = $questionSuivante;

        return $this;
    }

    public function getRecommendation(): ?Recommendation
    {
        return $this->recommendation;
    }

    public function setRecommendation(?Recommendation $recommendation): static
    {
        $this->recommendation = $recommendation;

        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): static
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setQuestion($this);
        }

        return $this;
    }
}
