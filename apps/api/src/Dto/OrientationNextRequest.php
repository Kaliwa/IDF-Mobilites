<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrée de POST /api/orientation/next : décrit l'état courant du parcours.
 *
 * - scenario        : code de l'événement de vie en cours ;
 * - currentQuestion : code de la question à laquelle l'utilisateur vient de répondre
 *                     (null ou absent => on demande la première question du parcours) ;
 * - answers         : codes des réponses sélectionnées pour cette question.
 */
final class OrientationNextRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    public ?string $scenario = null;

    #[Assert\Length(max: 64)]
    public ?string $currentQuestion = null;

    /**
     * @var string[]
     */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Length(max: 64),
    ])]
    public array $answers = [];
}
