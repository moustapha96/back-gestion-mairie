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

    public function findFirst(): ?NiveauValidation
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.ordre', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

     /** Niveau suivant (ordre strictement supérieur au courant) */
    public function findNext(int $currentOrdre): ?NiveauValidation
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.ordre > :o')->setParameter('o', $currentOrdre)
            ->orderBy('n.ordre', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Niveau précédent (ordre strictement inférieur au courant) */
    public function findPrevious(int $currentOrdre): ?NiveauValidation
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.ordre < :o')->setParameter('o', $currentOrdre)
            ->orderBy('n.ordre', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
}
