<?php

namespace App\Service;

use App\Dto\OrientationNextRequest;
use App\Entity\Answer;
use App\Entity\EventScenario;
use App\Entity\Question;
use App\Entity\Recommendation;
use App\Enum\TypeQuestion;
use App\Exception\OrientationException;
use App\Repository\EventScenarioRepository;
use App\Repository\QuestionRepository;

/**
 * Moteur d'orientation : parcourt l'arbre de décision configuré en base
 * (EventScenario -> Question -> Answer -> Question|Recommendation).
 *
 * Le moteur est sans état : le front renvoie à chaque étape le code du scénario,
 * la question courante et les réponses choisies ; le moteur en déduit l'étape suivante.
 */
final class OrientationEngine
{
    public function __construct(
        private readonly EventScenarioRepository $scenarioRepository,
        private readonly QuestionRepository $questionRepository,
    ) {
    }

    /**
     * Liste des événements de vie proposés en entrée de parcours.
     *
     * @return array<int, array{code: string, label: string, description: ?string, icone: ?string}>
     */
    public function listEvents(): array
    {
        return array_map(
            static fn (EventScenario $scenario): array => [
                'code' => (string) $scenario->getCode(),
                'label' => (string) $scenario->getLabel(),
                'description' => $scenario->getDescription(),
                'icone' => $scenario->getIcone(),
            ],
            $this->scenarioRepository->findAllOrdered(),
        );
    }

    /**
     * Détermine l'étape suivante du parcours (question ou recommandation finale).
     *
     * @return array{type: string, question?: array<string, mixed>, recommendation?: array<string, mixed>}
     */
    public function resolveNext(OrientationNextRequest $request): array
    {
        $scenario = $this->scenarioRepository->findOneBy(['code' => $request->scenario]);
        if (!$scenario instanceof EventScenario) {
            throw OrientationException::notFound(sprintf('Scénario « %s » introuvable.', (string) $request->scenario));
        }

        // Début de parcours : aucune question encore répondue.
        if (null === $request->currentQuestion || '' === $request->currentQuestion) {
            $premiere = $scenario->getQuestionInitiale();
            if (!$premiere instanceof Question) {
                throw OrientationException::misconfigured(sprintf('Le scénario « %s » n\'a pas de question initiale.', (string) $scenario->getCode()));
            }

            return $this->questionStep($premiere, $scenario);
        }

        $question = $this->questionRepository->findOneByScenarioAndCode($scenario, $request->currentQuestion);
        if (!$question instanceof Question) {
            throw OrientationException::notFound(sprintf('Question « %s » introuvable dans ce parcours.', $request->currentQuestion));
        }

        // Résolution de la transition selon le type de question.
        if (TypeQuestion::CHOIX_UNIQUE === $question->getType()) {
            $answer = $this->resolveSingleAnswer($question, $request->answers);
            $questionSuivante = $answer->getQuestionSuivante();
            $recommendation = $answer->getRecommendation();
        } else {
            // Choix multiple : la transition est portée par la question elle-même.
            if ([] === $request->answers) {
                throw OrientationException::invalid('Veuillez sélectionner au moins une réponse.');
            }
            $questionSuivante = $question->getQuestionSuivante();
            $recommendation = $question->getRecommendation();
        }

        if ($recommendation instanceof Recommendation) {
            return $this->recommendationStep($recommendation);
        }

        if ($questionSuivante instanceof Question) {
            return $this->questionStep($questionSuivante, $scenario);
        }

        throw OrientationException::misconfigured('Aucune transition définie pour cette réponse.');
    }

    /**
     * Sélectionne et valide l'unique réponse attendue pour une question à choix unique.
     *
     * @param string[] $answerCodes
     */
    private function resolveSingleAnswer(Question $question, array $answerCodes): Answer
    {
        $code = $answerCodes[0] ?? null;
        if (null === $code) {
            throw OrientationException::invalid('Veuillez sélectionner une réponse.');
        }

        foreach ($question->getAnswers() as $answer) {
            if ($answer->getCode() === $code) {
                return $answer;
            }
        }

        throw OrientationException::invalid(sprintf('Réponse « %s » invalide pour cette question.', $code));
    }

    /**
     * @return array{type: string, question: array<string, mixed>}
     */
    private function questionStep(Question $question, EventScenario $scenario): array
    {
        $answers = [];
        foreach ($question->getAnswers() as $answer) {
            $answers[] = [
                'code' => (string) $answer->getCode(),
                'libelle' => (string) $answer->getLibelle(),
            ];
        }

        return [
            'type' => 'question',
            'question' => [
                'code' => (string) $question->getCode(),
                'libelle' => (string) $question->getLibelle(),
                'type' => $question->getType()->value,
                'answers' => $answers,
                // Repères de progression pour la barre côté front.
                'etape' => $question->getOrdre(),
                'etapeMax' => $scenario->getQuestions()->count(),
            ],
        ];
    }

    /**
     * @return array{type: string, recommendation: array<string, mixed>}
     */
    private function recommendationStep(Recommendation $recommendation): array
    {
        return [
            'type' => 'recommendation',
            'recommendation' => [
                'code' => (string) $recommendation->getCode(),
                'titre' => (string) $recommendation->getTitre(),
                'description' => $recommendation->getDescription(),
                'offres' => $recommendation->getOffres(),
                'aides' => $recommendation->getAides(),
                'verification' => $recommendation->getVerification(),
                'ctaLabel' => $recommendation->getCtaLabel(),
                'ctaUrl' => $recommendation->getCtaUrl(),
            ],
        ];
    }
}
