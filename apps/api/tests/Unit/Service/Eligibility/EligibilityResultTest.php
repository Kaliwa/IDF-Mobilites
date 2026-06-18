<?php

namespace App\Tests\Unit\Service\Eligibility;

use App\Enum\StatutVerification;
use App\Service\Eligibility\EligibilityResult;
use PHPUnit\Framework\TestCase;

final class EligibilityResultTest extends TestCase
{
    public function testEstValideReturnsTrueWhenStatutIsValide(): void
    {
        $result = new EligibilityResult(
            statut: StatutVerification::VALIDE,
            message: 'Éligibilité confirmée.',
        );

        $this->assertTrue($result->estValide());
    }

    public function testEstValideReturnsFalseWhenStatutIsRefuse(): void
    {
        $result = new EligibilityResult(
            statut: StatutVerification::REFUSE,
            message: 'Dossier refusé.',
        );

        $this->assertFalse($result->estValide());
    }

    public function testEstValideReturnsFalseWhenStatutIsEnAttente(): void
    {
        $result = new EligibilityResult(
            statut: StatutVerification::EN_ATTENTE,
            message: 'Vérification en cours.',
        );

        $this->assertFalse($result->estValide());
    }

    public function testDonneesDefaultsToEmptyArray(): void
    {
        $result = new EligibilityResult(
            statut: StatutVerification::VALIDE,
            message: 'OK',
        );

        $this->assertSame([], $result->donnees);
    }

    public function testFallbackRequisDefaultsToFalse(): void
    {
        $result = new EligibilityResult(
            statut: StatutVerification::EN_ATTENTE,
            message: 'En attente',
        );

        $this->assertFalse($result->fallbackRequis);
    }

    public function testSourceIsStoredCorrectly(): void
    {
        $result = new EligibilityResult(
            statut: StatutVerification::VALIDE,
            message: 'Certifié',
            source: 'CAF',
            donnees: ['numeroAllocataire' => '1234567'],
        );

        $this->assertSame('CAF', $result->source);
        $this->assertSame('1234567', $result->donnees['numeroAllocataire']);
    }
}
