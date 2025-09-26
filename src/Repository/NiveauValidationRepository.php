<?php

namespace App\Repository;

use App\Entity\NiveauValidation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NiveauValidation>
 *
 * @method NiveauValidation|null find($id, $lockMode = null, $lockVersion = null)
 * @method NiveauValidation|null findOneBy(array $criteria, array $orderBy = null)
 * @method NiveauValidation[]    findAll()
 * @method NiveauValidation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NiveauValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NiveauValidation::class);
    }

    public function findAllOrderedByOrdre(): array
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
//    /**
//     * @return NiveauValidation[] Returns an array of NiveauValidation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NiveauValidation
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
