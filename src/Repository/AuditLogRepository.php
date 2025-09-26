<?php
// src/Repository/AuditLogRepository.php
namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

final class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * @param array{
     *   page?:int,
     *   size?:int,
     *   sort?:string,
     *   event?:string|null,
     *   actorId?:int|null,
     *   entityClass?:string|null,
     *   entityId?:string|null,
     *   status?:string|null,
     *   requestId?:string|null,
     *   from?:\DateTimeInterface|null,
     *   to?:\DateTimeInterface|null
     * } $criteria
     *
     * @return array{items: array<AuditLog>, total:int, page:int, size:int, pages:int}
     */
    public function searchPaginated(array $criteria): array
    {
        // Validation des paramètres de pagination
        $page = max(1, (int)($criteria['page'] ?? 1));
        $size = (int)($criteria['size'] ?? 20);
        $size = min(max($size, 1), 100); // Limite entre 1 et 100

        // Gestion du tri
        $sort = $criteria['sort'] ?? 'createdAt,DESC';
        [$sortField, $sortDir] = array_pad(explode(',', $sort, 2), 2, 'DESC');

        // Validation du champ de tri
        $allowedSortFields = ['event', 'status', 'actorId', 'entityClass', 'entityId', 'requestId', 'createdAt'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'createdAt';

        // Validation de la direction de tri
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        // Création du QueryBuilder
        $qb = $this->createQueryBuilder('a');

        // Application des filtres
        $this->applyFilters($qb, $criteria);

        // Application du tri
        $qb->orderBy('a.' . $sortField, $sortDir);

        // Pagination
        $qb->setFirstResult(($page - 1) * $size)
           ->setMaxResults($size);

        // Récupération des résultats
        $items = $qb->getQuery()->getResult();

        // Compte total - Utilisation de getSingleScalarResult() avec vérification
        $total = $this->getTotalCount($qb, $criteria);

        // Calcul du nombre de pages
        $pages = $total > 0 ? (int)ceil($total / $size) : 0;

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'size'  => $size,
            'pages' => $pages,
        ];
    }

    /**
     * Applique les filtres au QueryBuilder
     */
    private function applyFilters(QueryBuilder $qb, array $criteria): void
    {
        if (!empty($criteria['event'])) {
            $qb->andWhere('a.event = :event')
               ->setParameter('event', $criteria['event']);
        }

        if (!empty($criteria['actorId'])) {
            $qb->andWhere('a.actorId = :actorId')
               ->setParameter('actorId', (int)$criteria['actorId']);
        }

        if (!empty($criteria['entityClass'])) {
            $qb->andWhere('a.entityClass = :entityClass')
               ->setParameter('entityClass', $criteria['entityClass']);
        }

        if (!empty($criteria['entityId'])) {
            $qb->andWhere('a.entityId = :entityId')
               ->setParameter('entityId', $criteria['entityId']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['requestId'])) {
            $qb->andWhere('a.requestId = :requestId')
               ->setParameter('requestId', $criteria['requestId']);
        }

        if (!empty($criteria['from']) && $criteria['from'] instanceof \DateTimeInterface) {
            $qb->andWhere('a.createdAt >= :from')
               ->setParameter('from', $criteria['from']);
        }

        if (!empty($criteria['to']) && $criteria['to'] instanceof \DateTimeInterface) {
            $qb->andWhere('a.createdAt <= :to')
               ->setParameter('to', $criteria['to']);
        }
    }

    /**
     * Récupère le nombre total d'éléments en fonction des filtres
     */
    private function getTotalCount(QueryBuilder $qb, array $criteria): int
    {
        // Créer une nouvelle instance de QueryBuilder pour le compte
        $countQb = $this->createQueryBuilder('a');

        // Appliquer les mêmes filtres
        $this->applyFilters($countQb, $criteria);

        // Compter les résultats
        try {
            return (int)$countQb->select('COUNT(a.id)')
                                ->getQuery()
                                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // Si aucun résultat n'est trouvé, retourner 0
            return 0;
        }
    }
}
