<?php

namespace App\Repository;

use App\Entity\Parcelle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParcelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcelle::class);
    }

    /**
     * Pagination/tri/filtres pour les parcelles.
     *
     * @return array{data: array<int, Parcelle>, meta: array<string, int|string>}
     */
    public function findPaginated(
        int $page = 1,
        int $size = 10,
        ?string $search = null,
        ?string $statut = null,
        ?int $lotissementId = null,
        ?string $typeParcelle = null,
        ?string $sort = 'id,DESC'
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.lotissement', 'l')->addSelect('l')
            ->leftJoin('p.proprietaire', 'u')->addSelect('u'); // si besoin côté front

        // Recherche plein-texte simple
        if ($search) {
            $qb->andWhere('(p.numero LIKE :q OR p.statut LIKE :q OR p.typeParcelle LIKE :q OR p.tfDe LIKE :q OR l.nom LIKE :q)')
               ->setParameter('q', "%$search%");
        }

        if ($statut) {
            $qb->andWhere('p.statut = :statut')->setParameter('statut', $statut);
        }

        if ($lotissementId) {
            $qb->andWhere('l.id = :lid')->setParameter('lid', $lotissementId);
        }

        if ($typeParcelle) {
            $qb->andWhere('p.typeParcelle = :tp')->setParameter('tp', $typeParcelle);
        }

        // Tri sécurisé (whitelist)
        $allowed = [
            'id'           => 'p.id',
            'numero'       => 'p.numero',
            'surface'      => 'p.surface',
            'statut'       => 'p.statut',
            'typeParcelle' => 'p.typeParcelle',
            'lotissement'  => 'l.nom', // tri par nom du lotissement
        ];

        $field = 'id'; $dir = 'DESC';
        if ($sort) {
            [$f, $d] = array_map('trim', explode(',', $sort) + [null, null]);
            if ($f && isset($allowed[$f])) {
                $field = $f;
            }
            if ($d && in_array(strtoupper($d), ['ASC', 'DESC'], true)) {
                $dir = strtoupper($d);
            }
        }
        $qb->orderBy($allowed[$field], $dir);

        // Total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(p.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        // Pagination
        $page = max(1, $page);
        $size = max(1, min(200, $size));
        $qb->setFirstResult(($page - 1) * $size)->setMaxResults($size);

        $data = $qb->getQuery()->getResult();

        return [
            'data' => $data,
            'meta' => [
                'page'  => $page,
                'size'  => $size,
                'total' => $total,
                'pages' => (int) ceil($total / $size),
                'sort'  => "{$field},{$dir}",
            ],
        ];
    }
}
