<?php

namespace App\Command;

use App\Entity\Answer;
use App\Entity\EventScenario;
use App\Entity\Question;
use App\Entity\Recommendation;
use App\Enum\TypeQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Peuple l'arbre de décision de l'orientation (équivalent de fixtures, sans dépendance dev).
 *
 * Idempotente : purge les scénarios existants puis recharge l'arbre déclaré dans getTree().
 * L'arbre est volontairement décrit sous forme de tableau pour rester facile à éditer.
 */
#[AsCommand(
    name: 'app:orientation:seed',
    description: 'Charge (ou recharge) les événements de vie, questions et recommandations.',
)]
final class SeedOrientationCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->purge();

        foreach ($this->getTree() as $scenarioData) {
            $this->buildScenario($scenarioData);
        }

        $this->em->flush();

        $io->success(sprintf('%d événement(s) de vie chargé(s).', count($this->getTree())));

        return Command::SUCCESS;
    }

    /**
     * Vide les tables de l'orientation dans un ordre compatible avec les contraintes FK.
     */
    private function purge(): void
    {
        // Les FK self/croisées sont en SET NULL ; on supprime les feuilles d'abord.
        foreach (['App\\Entity\\Answer', 'App\\Entity\\Question', 'App\\Entity\\Recommendation', 'App\\Entity\\EventScenario'] as $class) {
            $this->em->createQuery(sprintf('DELETE FROM %s', $class))->execute();
        }
    }

    /**
     * Construit un scénario complet en deux passes (création puis câblage des transitions).
     *
     * @param array<string, mixed> $data
     */
    private function buildScenario(array $data): void
    {
        $scenario = (new EventScenario())
            ->setCode($data['code'])
            ->setLabel($data['label'])
            ->setDescription($data['description'] ?? null)
            ->setIcone($data['icone'] ?? null)
            ->setOrdre($data['ordre'] ?? 0);
        $this->em->persist($scenario);

        // Passe 1 : créer recommandations et questions sans leurs transitions.
        /** @var array<string, Recommendation> $recos */
        $recos = [];
        foreach ($data['recommendations'] ?? [] as $r) {
            $reco = (new Recommendation())
                ->setCode($r['code'])
                ->setTitre($r['titre'])
                ->setDescription($r['description'] ?? null)
                ->setOffres($r['offres'] ?? [])
                ->setAides($r['aides'] ?? [])
                ->setVerification($r['verification'] ?? null)
                ->setCtaLabel($r['ctaLabel'] ?? 'Souscrire cette offre')
                ->setCtaUrl($r['ctaUrl'] ?? '/register');
            $scenario->addRecommendation($reco);
            $this->em->persist($reco);
            $recos[$r['code']] = $reco;
        }

        /** @var array<string, Question> $questions */
        $questions = [];
        foreach ($data['questions'] ?? [] as $q) {
            $question = (new Question())
                ->setCode($q['code'])
                ->setLibelle($q['libelle'])
                ->setType(TypeQuestion::from($q['type'] ?? 'single_choice'))
                ->setOrdre($q['ordre'] ?? 0);
            $scenario->addQuestion($question);
            $this->em->persist($question);
            $questions[$q['code']] = $question;
        }

        // Passe 2 : câbler les transitions maintenant que tout existe.
        foreach ($data['questions'] ?? [] as $q) {
            $question = $questions[$q['code']];

            // Transition portée par la question (choix multiple).
            if (isset($q['next'])) {
                $question->setQuestionSuivante($questions[$q['next']]);
            }
            if (isset($q['reco'])) {
                $question->setRecommendation($recos[$q['reco']]);
            }

            // Réponses + transitions portées par la réponse (choix unique).
            foreach ($q['answers'] ?? [] as $i => $a) {
                $answer = (new Answer())
                    ->setCode($a['code'])
                    ->setLibelle($a['libelle'])
                    ->setOrdre($a['ordre'] ?? $i);
                if (isset($a['next'])) {
                    $answer->setQuestionSuivante($questions[$a['next']]);
                }
                if (isset($a['reco'])) {
                    $answer->setRecommendation($recos[$a['reco']]);
                }
                $question->addAnswer($answer);
                $this->em->persist($answer);
            }
        }

        if (isset($data['questionInitiale'])) {
            $scenario->setQuestionInitiale($questions[$data['questionInitiale']]);
        }
    }

    /**
     * Définition déclarative de l'arbre de décision.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTree(): array
    {
        return [
            // ---------------------------------------------------------------
            // 1. Je deviens étudiant (parcours le plus riche, multi-étapes)
            // ---------------------------------------------------------------
            [
                'code' => 'devenir_etudiant',
                'label' => 'Je deviens étudiant',
                'description' => 'Trouvez le forfait adapté à vos études et aux aides auxquelles vous avez droit.',
                'icone' => 'compass',
                'ordre' => 1,
                'questionInitiale' => 'age',
                'questions' => [
                    [
                        'code' => 'age', 'ordre' => 1, 'type' => 'single_choice',
                        'libelle' => 'Quel âge aurez-vous à la rentrée ?',
                        'answers' => [
                            ['code' => 'moins_26', 'libelle' => 'Moins de 26 ans', 'next' => 'boursier'],
                            ['code' => 'plus_26', 'libelle' => '26 ans ou plus', 'next' => 'solidarite'],
                        ],
                    ],
                    [
                        'code' => 'boursier', 'ordre' => 2, 'type' => 'single_choice',
                        'libelle' => 'Êtes-vous boursier sur critères sociaux ?',
                        'answers' => [
                            ['code' => 'oui', 'libelle' => 'Oui, je suis boursier', 'reco' => 'imagine_r_boursier'],
                            ['code' => 'non', 'libelle' => 'Non', 'reco' => 'imagine_r'],
                        ],
                    ],
                    [
                        'code' => 'solidarite', 'ordre' => 2, 'type' => 'single_choice',
                        'libelle' => 'Vos revenus vous ouvrent-ils droit à la tarification solidarité ?',
                        'answers' => [
                            ['code' => 'oui', 'libelle' => 'Oui', 'reco' => 'solidarite'],
                            ['code' => 'non', 'libelle' => 'Non / je ne sais pas', 'reco' => 'navigo_annuel'],
                        ],
                    ],
                ],
                'recommendations' => [
                    [
                        'code' => 'imagine_r', 'titre' => 'Forfait Imagine R Étudiant',
                        'description' => 'L\'abonnement annuel à tarif réduit pour les étudiants de moins de 26 ans, valable 7j/7 sur tout le réseau.',
                        'offres' => [
                            ['code' => 'imagine_r_etudiant', 'label' => 'Imagine R Étudiant', 'description' => 'Abonnement annuel toutes zones, voyages illimités 7j/7.'],
                        ],
                        'aides' => [
                            ['code' => 'tarif_jeune', 'label' => 'Tarif jeune', 'description' => 'Tarif annuel avantageux réservé aux moins de 26 ans.'],
                        ],
                        'ctaLabel' => 'Souscrire Imagine R',
                    ],
                    [
                        'code' => 'imagine_r_boursier', 'titre' => 'Imagine R + aide bourse',
                        'description' => 'Le forfait Imagine R, complété par une prise en charge liée à votre bourse.',
                        'offres' => [
                            ['code' => 'imagine_r_etudiant', 'label' => 'Imagine R Étudiant', 'description' => 'Abonnement annuel toutes zones, voyages illimités 7j/7.'],
                        ],
                        'aides' => [
                            ['code' => 'tarif_jeune', 'label' => 'Tarif jeune', 'description' => 'Tarif annuel réservé aux moins de 26 ans.'],
                            ['code' => 'aide_bourse', 'label' => 'Prise en charge bourse', 'description' => 'Réduction supplémentaire (jusqu\'à la gratuité) selon l\'échelon de bourse, financée par la Région.'],
                        ],
                        'verification' => ['aideCode' => 'aide_bourse', 'label' => 'votre statut boursier', 'methodes' => ['france_connect', 'justificatif']],
                        'ctaLabel' => 'Souscrire Imagine R',
                    ],
                    [
                        'code' => 'solidarite', 'titre' => 'Tarification solidarité transport',
                        'description' => 'Un forfait Navigo à tarif réduit grâce à la tarification solidarité.',
                        'offres' => [
                            ['code' => 'navigo_mois', 'label' => 'Forfait Navigo Mois', 'description' => 'Abonnement mensuel toutes zones.'],
                        ],
                        'aides' => [
                            ['code' => 'solidarite_transport', 'label' => 'Tarification solidarité (50 % ou 75 %)', 'description' => 'Réduction de 50 % ou 75 % selon vos ressources et votre situation.'],
                        ],
                        'verification' => ['aideCode' => 'solidarite_transport', 'label' => 'vos droits à la tarification solidarité', 'methodes' => ['france_connect', 'justificatif']],
                        'ctaLabel' => 'Vérifier mon éligibilité',
                    ],
                    [
                        'code' => 'navigo_annuel', 'titre' => 'Forfait Navigo Annuel',
                        'description' => 'L\'abonnement annuel toutes zones, sans condition d\'âge.',
                        'offres' => [
                            ['code' => 'navigo_annuel', 'label' => 'Navigo Annuel', 'description' => 'Engagement annuel, paiement mensualisé, voyages illimités.'],
                        ],
                        'aides' => [],
                        'ctaLabel' => 'Souscrire Navigo Annuel',
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 2. Mon enfant entre au collège / lycée
            // ---------------------------------------------------------------
            [
                'code' => 'enfant_scolaire',
                'label' => 'Mon enfant entre au collège / lycée',
                'description' => 'Le bon titre pour les trajets scolaires de votre enfant.',
                'icone' => 'document',
                'ordre' => 2,
                'questionInitiale' => 'scolarise_idf',
                'questions' => [
                    [
                        'code' => 'scolarise_idf', 'ordre' => 1, 'type' => 'single_choice',
                        'libelle' => 'Votre enfant est-il scolarisé en Île-de-France ?',
                        'answers' => [
                            ['code' => 'oui', 'libelle' => 'Oui', 'reco' => 'imagine_r_scolaire'],
                            ['code' => 'non', 'libelle' => 'Non', 'reco' => 'renseignement'],
                        ],
                    ],
                ],
                'recommendations' => [
                    [
                        'code' => 'imagine_r_scolaire', 'titre' => 'Forfait Imagine R Scolaire',
                        'description' => 'L\'abonnement annuel à tarif réduit pour les collégiens et lycéens.',
                        'offres' => [
                            ['code' => 'imagine_r_scolaire', 'label' => 'Imagine R Scolaire', 'description' => 'Abonnement annuel toutes zones pour les élèves.'],
                        ],
                        'aides' => [
                            ['code' => 'tarif_scolaire', 'label' => 'Tarif scolaire', 'description' => 'Tarif réduit réservé aux élèves, voyages illimités 7j/7.'],
                        ],
                        'ctaLabel' => 'Souscrire Imagine R Scolaire',
                    ],
                    [
                        'code' => 'renseignement', 'titre' => 'Renseignez-vous auprès de votre région',
                        'description' => 'Hors Île-de-France, les aides au transport scolaire dépendent de votre région de résidence.',
                        'offres' => [],
                        'aides' => [],
                        'ctaLabel' => 'Créer mon compte',
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 3. Je change de travail (prise en charge employeur 50 %)
            // ---------------------------------------------------------------
            [
                'code' => 'changer_travail',
                'label' => 'Je change de travail',
                'description' => 'Optimisez votre abonnement avec la prise en charge employeur.',
                'icone' => 'card',
                'ordre' => 3,
                'questionInitiale' => 'frequence',
                'questions' => [
                    [
                        'code' => 'frequence', 'ordre' => 1, 'type' => 'single_choice',
                        'libelle' => 'À quelle fréquence vous rendez-vous au travail ?',
                        'answers' => [
                            ['code' => 'quotidien', 'libelle' => 'Tous les jours', 'next' => 'employeur'],
                            ['code' => 'variable', 'libelle' => 'Quelques jours par semaine', 'reco' => 'liberte_plus'],
                        ],
                    ],
                    [
                        'code' => 'employeur', 'ordre' => 2, 'type' => 'single_choice',
                        'libelle' => 'Votre employeur prend-il en charge 50 % de l\'abonnement ?',
                        'answers' => [
                            ['code' => 'oui', 'libelle' => 'Oui', 'reco' => 'navigo_employeur'],
                            ['code' => 'non', 'libelle' => 'Non / je ne sais pas', 'reco' => 'navigo_annuel_w'],
                        ],
                    ],
                ],
                'recommendations' => [
                    [
                        'code' => 'navigo_employeur', 'titre' => 'Navigo Annuel + prise en charge employeur',
                        'description' => 'Le forfait annuel, dont la moitié est remboursée par votre employeur.',
                        'offres' => [
                            ['code' => 'navigo_annuel', 'label' => 'Navigo Annuel', 'description' => 'Voyages illimités toutes zones, paiement mensualisé.'],
                        ],
                        'aides' => [
                            ['code' => 'prise_charge_employeur', 'label' => 'Prise en charge employeur 50 %', 'description' => 'Remboursement obligatoire de 50 % du titre par l\'employeur.'],
                        ],
                        'ctaLabel' => 'Souscrire Navigo Annuel',
                    ],
                    [
                        'code' => 'navigo_annuel_w', 'titre' => 'Forfait Navigo Annuel',
                        'description' => 'L\'abonnement annuel toutes zones pour les trajets quotidiens.',
                        'offres' => [
                            ['code' => 'navigo_annuel', 'label' => 'Navigo Annuel', 'description' => 'Voyages illimités, paiement mensualisé.'],
                        ],
                        'aides' => [
                            ['code' => 'prise_charge_employeur', 'label' => 'Prise en charge employeur 50 %', 'description' => 'Pensez à demander le remboursement de 50 % à votre employeur.'],
                        ],
                        'ctaLabel' => 'Souscrire Navigo Annuel',
                    ],
                    [
                        'code' => 'liberte_plus', 'titre' => 'Navigo Liberté+',
                        'description' => 'Le paiement à l\'usage, idéal pour des trajets occasionnels.',
                        'offres' => [
                            ['code' => 'navigo_liberte_plus', 'label' => 'Navigo Liberté+', 'description' => 'Trajets décomptés et facturés en fin de mois, sans forfait.'],
                        ],
                        'aides' => [
                            ['code' => 'prise_charge_employeur', 'label' => 'Prise en charge employeur 50 %', 'description' => 'La prise en charge employeur s\'applique aussi à Navigo Liberté+.'],
                        ],
                        'ctaLabel' => 'Activer Liberté+',
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 4. Je pars à la retraite
            // ---------------------------------------------------------------
            [
                'code' => 'partir_retraite',
                'label' => 'Je pars à la retraite',
                'description' => 'Adaptez votre abonnement à votre nouveau rythme de déplacements.',
                'icone' => 'clock',
                'ordre' => 4,
                'questionInitiale' => 'rythme',
                'questions' => [
                    [
                        'code' => 'rythme', 'ordre' => 1, 'type' => 'single_choice',
                        'libelle' => 'À quelle fréquence pensez-vous voyager ?',
                        'answers' => [
                            ['code' => 'souvent', 'libelle' => 'Souvent (plusieurs fois par semaine)', 'reco' => 'navigo_annuel_senior'],
                            ['code' => 'occasionnel', 'libelle' => 'De temps en temps', 'reco' => 'liberte_plus_senior'],
                        ],
                    ],
                ],
                'recommendations' => [
                    [
                        'code' => 'navigo_annuel_senior', 'titre' => 'Forfait Navigo Annuel',
                        'description' => 'L\'abonnement annuel toutes zones pour des déplacements fréquents.',
                        'offres' => [
                            ['code' => 'navigo_annuel', 'label' => 'Navigo Annuel', 'description' => 'Voyages illimités, paiement mensualisé.'],
                        ],
                        'aides' => [
                            ['code' => 'solidarite_transport', 'label' => 'Tarification solidarité', 'description' => 'Selon vos ressources, une réduction de 50 % ou 75 % est possible.'],
                            ['code' => 'amethyste', 'label' => 'Forfait Améthyste', 'description' => 'Forfait à tarif réduit ou gratuit pour les seniors, selon votre département de résidence et vos ressources.'],
                        ],
                        'ctaLabel' => 'Souscrire Navigo Annuel',
                    ],
                    [
                        'code' => 'liberte_plus_senior', 'titre' => 'Navigo Liberté+',
                        'description' => 'Le paiement à l\'usage, sans engagement, parfait pour des trajets ponctuels.',
                        'offres' => [
                            ['code' => 'navigo_liberte_plus', 'label' => 'Navigo Liberté+', 'description' => 'Trajets facturés à l\'usage en fin de mois.'],
                        ],
                        'aides' => [],
                        'ctaLabel' => 'Activer Liberté+',
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 5. J'arrive en Île-de-France
            // ---------------------------------------------------------------
            [
                'code' => 'arriver_idf',
                'label' => 'J\'arrive en Île-de-France',
                'description' => 'Découvrez l\'offre de transport adaptée à votre installation.',
                'icone' => 'search',
                'ordre' => 5,
                'questionInitiale' => 'profil',
                'questions' => [
                    [
                        'code' => 'profil', 'ordre' => 1, 'type' => 'single_choice',
                        'libelle' => 'Quelle est votre situation principale ?',
                        'answers' => [
                            ['code' => 'etudiant', 'libelle' => 'Étudiant', 'reco' => 'imagine_r_new'],
                            ['code' => 'salarie', 'libelle' => 'Salarié', 'reco' => 'navigo_annuel_new'],
                            ['code' => 'autre', 'libelle' => 'Autre', 'reco' => 'liberte_plus_new'],
                        ],
                    ],
                ],
                'recommendations' => [
                    [
                        'code' => 'imagine_r_new', 'titre' => 'Forfait Imagine R Étudiant',
                        'description' => 'L\'abonnement annuel à tarif réduit pour les étudiants de moins de 26 ans.',
                        'offres' => [
                            ['code' => 'imagine_r_etudiant', 'label' => 'Imagine R Étudiant', 'description' => 'Abonnement annuel toutes zones.'],
                        ],
                        'aides' => [
                            ['code' => 'tarif_jeune', 'label' => 'Tarif jeune', 'description' => 'Tarif réservé aux moins de 26 ans.'],
                        ],
                        'ctaLabel' => 'Souscrire Imagine R',
                    ],
                    [
                        'code' => 'navigo_annuel_new', 'titre' => 'Forfait Navigo Annuel',
                        'description' => 'L\'abonnement de référence pour vos trajets domicile-travail.',
                        'offres' => [
                            ['code' => 'navigo_annuel', 'label' => 'Navigo Annuel', 'description' => 'Voyages illimités toutes zones.'],
                        ],
                        'aides' => [
                            ['code' => 'prise_charge_employeur', 'label' => 'Prise en charge employeur 50 %', 'description' => 'Si vous travaillez, 50 % du titre est remboursé par l\'employeur.'],
                        ],
                        'ctaLabel' => 'Souscrire Navigo Annuel',
                    ],
                    [
                        'code' => 'liberte_plus_new', 'titre' => 'Navigo Liberté+',
                        'description' => 'Le paiement à l\'usage pour découvrir le réseau à votre rythme.',
                        'offres' => [
                            ['code' => 'navigo_liberte_plus', 'label' => 'Navigo Liberté+', 'description' => 'Trajets facturés à l\'usage, sans forfait.'],
                        ],
                        'aides' => [],
                        'ctaLabel' => 'Activer Liberté+',
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 6. J'ai droit à une réduction ? (profils « Tarif Réduit » & aides)
            // ---------------------------------------------------------------
            [
                'code' => 'tarifs_reduits',
                'label' => 'J\'ai droit à une réduction ?',
                'description' => 'Vérifiez votre éligibilité aux tarifs réduits et aides selon votre profil.',
                'icone' => 'lifebuoy',
                'ordre' => 6,
                'questionInitiale' => 'profil_reduction',
                'questions' => [
                    [
                        'code' => 'profil_reduction', 'ordre' => 1, 'type' => 'single_choice',
                        'libelle' => 'Quelle situation vous concerne ?',
                        'answers' => [
                            ['code' => 'aides_sociales', 'libelle' => 'Je bénéficie d\'aides sociales (RSA, CSS, ASS…)', 'reco' => 'reduit_solidarite'],
                            ['code' => 'famille_nombreuse', 'libelle' => 'J\'ai une carte « Familles nombreuses »', 'reco' => 'reduit_famille'],
                            ['code' => 'invalidite', 'libelle' => 'Je suis en situation de handicap / d\'invalidité', 'reco' => 'reduit_invalidite'],
                            ['code' => 'enfant', 'libelle' => 'C\'est pour un enfant de moins de 10 ans', 'next' => 'age_enfant'],
                            ['code' => 'groupe', 'libelle' => 'C\'est pour un groupe de jeunes / une sortie scolaire', 'reco' => 'reduit_groupe'],
                        ],
                    ],
                    [
                        'code' => 'age_enfant', 'ordre' => 2, 'type' => 'single_choice',
                        'libelle' => 'Quel âge a l\'enfant ?',
                        'answers' => [
                            ['code' => 'moins_4', 'libelle' => 'Moins de 4 ans', 'reco' => 'reduit_enfant_gratuit'],
                            ['code' => '4_10', 'libelle' => 'De 4 ans à moins de 10 ans', 'reco' => 'reduit_enfant'],
                        ],
                    ],
                ],
                'recommendations' => [
                    [
                        'code' => 'reduit_solidarite', 'titre' => 'Tarification solidarité transport',
                        'description' => 'En tant que bénéficiaire d\'aides sociales, vous avez droit à un titre à tarif réduit, voire gratuit.',
                        'offres' => [
                            ['code' => 'navigo_mois', 'label' => 'Forfait Navigo Mois', 'description' => 'Abonnement mensuel toutes zones à tarif solidaire.'],
                        ],
                        'aides' => [
                            ['code' => 'solidarite_transport', 'label' => 'Solidarité Transport (50 %, 75 % ou gratuité)', 'description' => 'Réduction de 50 % ou 75 %, voire gratuité, selon l\'aide sociale dont vous bénéficiez et vos ressources.'],
                        ],
                        'verification' => ['aideCode' => 'solidarite_transport', 'label' => 'vos droits à la tarification solidarité', 'methodes' => ['france_connect', 'justificatif']],
                        'ctaLabel' => 'Vérifier mon éligibilité',
                    ],
                    [
                        'code' => 'reduit_famille', 'titre' => 'Réduction Familles nombreuses',
                        'description' => 'Votre carte « Familles nombreuses » ouvre droit à une réduction sur vos titres de transport.',
                        'offres' => [
                            ['code' => 'billets_reduit', 'label' => 'Billets & forfaits à tarif réduit', 'description' => 'Tarif réduit appliqué sur présentation de la carte « Familles nombreuses ».'],
                        ],
                        'aides' => [
                            ['code' => 'famille_nombreuse', 'label' => 'Carte « Familles nombreuses »', 'description' => 'Réduction de 30 % à 75 % selon le nombre d\'enfants, sur justificatif.'],
                        ],
                        'verification' => ['aideCode' => 'famille_nombreuse', 'label' => 'votre carte « Familles nombreuses »', 'methodes' => ['france_connect', 'justificatif']],
                        'ctaLabel' => 'Créer mon compte',
                    ],
                    [
                        'code' => 'reduit_invalidite', 'titre' => 'Tarif réduit invalidité',
                        'description' => 'Les personnes en situation d\'invalidité bénéficient d\'un tarif réduit, ainsi que leur accompagnateur.',
                        'offres' => [
                            ['code' => 'billets_reduit', 'label' => 'Titres à tarif réduit', 'description' => 'Tarif réduit sur les titres, sur présentation d\'un justificatif d\'invalidité.'],
                        ],
                        'aides' => [
                            ['code' => 'tarif_invalidite', 'label' => 'Tarif réduit invalidité', 'description' => 'Tarif réduit accordé sur justificatif.'],
                            ['code' => 'accompagnant', 'label' => 'Tarif réduit pour l\'accompagnant', 'description' => 'L\'accompagnateur d\'une personne invalide bénéficie également du tarif réduit.'],
                        ],
                        'verification' => ['aideCode' => 'tarif_invalidite', 'label' => 'votre justificatif d\'invalidité', 'methodes' => ['france_connect', 'justificatif']],
                        'ctaLabel' => 'Vérifier mon éligibilité',
                    ],
                    [
                        'code' => 'reduit_enfant_gratuit', 'titre' => 'Gratuité pour les moins de 4 ans',
                        'description' => 'Les enfants de moins de 4 ans voyagent gratuitement sur le réseau.',
                        'offres' => [],
                        'aides' => [
                            ['code' => 'gratuite_petite_enfance', 'label' => 'Gratuité moins de 4 ans', 'description' => 'Aucun titre nécessaire pour les enfants de moins de 4 ans.'],
                        ],
                        'ctaLabel' => 'En savoir plus',
                    ],
                    [
                        'code' => 'reduit_enfant', 'titre' => 'Tarif réduit enfant (4 à 10 ans)',
                        'description' => 'Les enfants de 4 ans à moins de 10 ans voyagent à tarif réduit.',
                        'offres' => [
                            ['code' => 'billets_reduit', 'label' => 'Tickets à tarif réduit', 'description' => 'Titres à tarif réduit pour les enfants de 4 à moins de 10 ans.'],
                        ],
                        'aides' => [
                            ['code' => 'tarif_enfant', 'label' => 'Tarif réduit enfant', 'description' => 'Réduction appliquée pour les enfants de 4 ans à moins de 10 ans.'],
                        ],
                        'ctaLabel' => 'Créer mon compte',
                    ],
                    [
                        'code' => 'reduit_groupe', 'titre' => 'Tarifs réduits groupes & sorties scolaires',
                        'description' => 'Des tarifs dédiés existent pour les groupes de jeunes et les sorties scolaires, périscolaires et extrascolaires.',
                        'offres' => [
                            ['code' => 'billets_groupe', 'label' => 'Titres groupes', 'description' => 'Titres à tarif réduit pour les déplacements en groupe.'],
                        ],
                        'aides' => [
                            ['code' => 'tarif_groupe_jeunes', 'label' => 'Tarif réduit groupes de jeunes', 'description' => 'Tarif dédié aux déplacements de groupes de jeunes.'],
                            ['code' => 'tarif_sorties_scolaires', 'label' => 'Tarif réduit sorties scolaires', 'description' => 'Tarif dédié aux sorties scolaires, périscolaires et extrascolaires.'],
                        ],
                        'ctaLabel' => 'En savoir plus',
                    ],
                ],
            ],
        ];
    }
}
