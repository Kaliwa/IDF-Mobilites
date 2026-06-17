<?php

namespace App\Repository;

use App\Entity\Journey;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Journey>
 */
class JourneyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journey::class);
    }

    /**
     * @return Journey[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.user = :user')
            ->setParameter('user', $user)
            ->orderBy('j.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

