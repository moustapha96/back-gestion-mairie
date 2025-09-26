<?php

namespace App\Repository;

use App\Entity\PlanLotissement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<PlanLotissement>
 */
class PlanLotissementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanLotissement::class);
    }

    public function save(PlanLotissement $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(PlanLotissement $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Pagination + recherche + tri.
     *
     * @param string|null $q             Recherche plein texte: description, version, nom lotissement
     * @param int|null    $lotissementId Filtre par lotissement
     * @param int         $page          Page 1-based
     * @param int         $limit         Taille de page
     * @param string      $sort          Champs de tri: id|dateCreation|version|lotissement
     * @param string      $dir           ASC|DESC
     *
     * @return array{items: array<int, PlanLotissement>, total:int, page:int, limit:int, pages:int}
     */
    public function findPaginated(
        ?string $q,
        ?int $lotissementId,
        int $page = 1,
        int $limit = 10,
        string $sort = 'dateCreation',
        string $dir = 'DESC'
    ): array {
        $page = max(1, $page);
        $limit = max(1, min(200, $limit));
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        // mapping de colonnes sûres
        $sortMap = [
            'id'            => 'p.id',
            'dateCreation'  => 'p.dateCreation',
            'version'       => 'p.version',
            'lotissement'   => 'l.nom',
        ];
        $sortCol = $sortMap[$sort] ?? 'p.dateCreation';

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.lotissement', 'l')
            ->addSelect('l');

        if ($q) {
            $qb->andWhere('(LOWER(p.description) LIKE :q OR LOWER(p.version) LIKE :q OR LOWER(l.nom) LIKE :q)')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($lotissementId) {
            $qb->andWhere('l.id = :lid')
               ->setParameter('lid', $lotissementId);
        }

        $qb->orderBy($sortCol, $dir);

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb, true);
        $total = count($paginator);

        return [
            'items' => iterator_to_array($paginator->getIterator(), false),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ];
    }
}
