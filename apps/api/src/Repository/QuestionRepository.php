<?php

namespace App\Repository;

use App\Entity\EventScenario;
use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Retrouve une question par son code au sein d'un scénario donné.
     */
    public function findOneByScenarioAndCode(EventScenario $scenario, string $code): ?Question
    {
        return $this->findOneBy(['scenario' => $scenario, 'code' => $code]);
    }
}
