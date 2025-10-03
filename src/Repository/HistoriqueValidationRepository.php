<?php

namespace App\Repository;

use App\Entity\HistoriqueValidation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueValidation>
 *
 * @method HistoriqueValidation|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriqueValidation|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriqueValidation[]    findAll()
 * @method HistoriqueValidation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriqueValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueValidation::class);
    }

    public function searchPaginated(array $filters, int $page, int $pageSize): array
    {
        $qb = $this->createQueryBuilder('h')
            ->leftJoin('h.request', 'd')
            ->leftJoin('h.validateur', 'u');

        // --- Filtres
        if (!empty($filters['demandeId'])) {
            $qb->andWhere('d.id = :did')->setParameter('did', (int) $filters['demandeId']);
        }
        if (!empty($filters['validateurId'])) {
            $qb->andWhere('u.id = :uid')->setParameter('uid', (int) $filters['validateurId']);
        }
        if (!empty($filters['action'])) {
            $qb->andWhere('h.action = :act')->setParameter('act', $filters['action']);
        }
        // Période: from/to sur dateAction
        if (!empty($filters['from'])) {
            $qb->andWhere('h.dateAction >= :from')->setParameter('from', new \DateTime($filters['from'].' 00:00:00'));
        }
        if (!empty($filters['to'])) {
            $qb->andWhere('h.dateAction <= :to')->setParameter('to', new \DateTime($filters['to'].' 23:59:59'));
        }

        // --- COUNT total (sans select scalaires)
        $total = (int) (clone $qb)->select('COUNT(h.id)')->getQuery()->getSingleScalarResult();

        // --- Sélection SCALAIRE (on ne renvoie pas des entités)
        $qb->select([
            'h.id            AS id',
            'h.action        AS action',
            'h.motif         AS motif',
            'h.dateAction    AS dateAction',

            // Champs essentiels de la Demande
            'd.id            AS demande_id',
            'd.typeDemande   AS demande_typeDemande',
            'd.statut        AS demande_statut',
            'd.dateCreation  AS demande_dateCreation',
            'd.numeroElecteur AS demande_numeroElecteur',
            'd.nom           AS demande_nom',
            'd.prenom        AS demande_prenom',

            // Champs essentiels du validateur
            'u.id            AS validateur_id',
            'u.prenom        AS validateur_prenom',
            'u.nom           AS validateur_nom',
            'u.email         AS validateur_email',
        ])
        ->orderBy('h.dateAction', 'DESC')
        ->setFirstResult(($page - 1) * $pageSize)
        ->setMaxResults($pageSize);

        // --- Résultat en arrays (pas d’entités hydratées)
        $rows = $qb->getQuery()->getArrayResult();

        // Option: normaliser les dates en string ici (évite format côté controller)
        $items = array_map(function(array $r) {
            $r['dateAction'] = isset($r['dateAction']) && $r['dateAction'] instanceof \DateTimeInterface
                ? $r['dateAction']->format('Y-m-d H:i:s') : null;

            $r['demande_dateCreation'] = isset($r['demande_dateCreation']) && $r['demande_dateCreation'] instanceof \DateTimeInterface
                ? $r['demande_dateCreation']->format('Y-m-d H:i:s') : null;

            return $r;
        }, $rows);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

}
