<?php

namespace App\Repository;

use App\Entity\Request;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Request>
 */
class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Request::class);
    }

    //    /**
    //     * @return Request[] Returns an array of Request objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Request
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByUtilisateur(User $user, int $offset = 0, int $limit = 50): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.utilisateur = :u')
            ->setParameter('u', $user)
            ->orderBy('d.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

     public function countByUtilisateur(User $user): int
    {
        return (int)$this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.utilisateur = :u')
            ->setParameter('u', $user)
            ->getQuery()->getSingleScalarResult();
    }
}
