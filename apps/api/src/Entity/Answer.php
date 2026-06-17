<?php

namespace App\Entity;

use App\Repository\AnswerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réponse possible à une question. Pour une question à choix unique, elle porte
 * la transition du parcours : soit vers la question suivante, soit vers une recommandation finale.
 */
#[ORM\Entity(repositoryClass: AnswerRepository::class)]
class Answer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Question $question = null;

    #[ORM\Column(length: 64)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $ordre = 0;

    /**
     * Question posée ensuite si cette réponse est choisie (choix unique).
     */
    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Question $questionSuivante = null;

    /**
     * Recommandation finale atteinte si cette réponse est choisie (choix unique).
     */
    #[ORM\ManyToOne(targetEntity: Recommendation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Recommendation $recommendation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

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
}
