<?php

// src/Repository/DemandeTerrainRepository.php
namespace App\Repository;

use App\Entity\DemandeTerrain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
    
class DemandeTerrainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeTerrain::class);
    }

    /**
     * @param array{
     *   page?:int,size?:int,sort?:string,
     *   search?:string, statut?:string, typeDemande?:string, typeDocument?:string,
     *   userId?:int, localiteId?:int,
     *   from?:?\DateTimeInterface, to?:?\DateTimeInterface
     * } $p
     * @return array{items: array<int,DemandeTerrain>, total:int, page:int, size:int, pages:int}
     */
    public function searchPaginated(array $p): array
    {
        $page = max(1, (int)($p['page'] ?? 1));
        $size = max(1, min(100, (int)($p['size'] ?? 10)));
        [$sortField, $sortDir] = (($p['sort'] ?? 'id,DESC') ? explode(',', $p['sort']) : ['id', 'DESC']);
        $sortField = trim($sortField);
        $sortDir   = strtoupper(trim($sortDir)) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.utilisateur', 'u')->addSelect('u')
            ->leftJoin('d.localite', 'l')->addSelect('l');

        // Recherche fulltext simple
        if (!empty($p['search'])) {
            $s = '%'.mb_strtolower($p['search']).'%';
            $qb->andWhere('LOWER(d.typeDemande) LIKE :s OR LOWER(d.usagePrevu) LIKE :s OR LOWER(u.nom) LIKE :s OR LOWER(u.prenom) LIKE :s OR LOWER(u.email) LIKE :s OR LOWER(l.nom) LIKE :s')
               ->setParameter('s', $s);
        }

        if (!empty($p['statut']))       $qb->andWhere('d.statut = :st')->setParameter('st', $p['statut']);
        if (!empty($p['typeDemande']))  $qb->andWhere('d.typeDemande = :td')->setParameter('td', $p['typeDemande']);
        if (!empty($p['typeDocument'])) $qb->andWhere('d.typeDocument = :tdoc')->setParameter('tdoc', $p['typeDocument']);
        if (!empty($p['userId']))       $qb->andWhere('u.id = :uid')->setParameter('uid', (int)$p['userId']);
        if (!empty($p['localiteId']))   $qb->andWhere('l.id = :lid')->setParameter('lid', (int)$p['localiteId']);

        if (!empty($p['from'])) $qb->andWhere('d.dateCreation >= :from')->setParameter('from', $p['from']);
        if (!empty($p['to']))   $qb->andWhere('d.dateCreation <= :to')->setParameter('to', $p['to']);

        // Tri sécurisé (white-list)
        $allowed = [
            'id' => 'd.id',
            'dateCreation' => 'd.dateCreation',
            'dateModification' => 'd.dateModification',
            'typeDemande' => 'd.typeDemande',
            'statut' => 'd.statut',
            'superficie' => 'd.superficie',
            'demandeur' => 'u.nom',
            'localite' => 'l.nom',
        ];
        $qb->orderBy($allowed[$sortField] ?? 'd.id', $sortDir);

        // Total
        $countQb = clone $qb;
        $total = (int)(clone $countQb)
            ->select('COUNT(d.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        // Items page
        $items = $qb
            ->setFirstResult(($page - 1) * $size)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'size'  => $size,
            'pages' => (int)ceil($total / $size),
        ];
    }
}
