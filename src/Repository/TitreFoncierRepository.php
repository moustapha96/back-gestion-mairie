<?php

namespace App\Repository;

use App\Entity\TitreFoncier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TitreFoncier>
 *
 * @method TitreFoncier|null find($id, $lockMode = null, $lockVersion = null)
 * @method TitreFoncier|null findOneBy(array $criteria, array $orderBy = null)
 * @method TitreFoncier[]    findAll()
 * @method TitreFoncier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TitreFoncierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TitreFoncier::class);
    }

//    /**
//     * @return TitreFoncier[] Returns an array of TitreFoncier objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TitreFoncier
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
