<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entrée de la vérification d'éligibilité via les services de l'État.
 */
final class EligibilityStateRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    public ?string $aideCode = null;
}
