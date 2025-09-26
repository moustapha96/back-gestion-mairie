<?php

namespace App\services;

use App\Entity\Localite;
use App\Entity\TitreFoncier;
use App\Repository\TitreFoncierRepository;
use Doctrine\ORM\EntityManagerInterface;

class TitreFoncierService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TitreFoncierRepository $repo
    ) {}

    /** @return array{items: array<int,TitreFoncier>, total:int} */
    public function searchPaginated(
        array $filters = [],
        int $page = 1,
        int $pageSize = 10,
        ?string $sortField = 'id',
        ?string $sortOrder = 'DESC'
    ): array {
        $qb = $this->repo->createQueryBuilder('t')
            ->leftJoin('t.quartier', 'q')->addSelect('q');

        // Filtres
        if (!empty($filters['numero'])) {
            $qb->andWhere('t.numero LIKE :numero')->setParameter('numero', '%' . $filters['numero'] . '%');
        }
        if (!empty($filters['numeroLot'])) {
            $qb->andWhere('t.numeroLot LIKE :numeroLot')->setParameter('numeroLot', '%' . $filters['numeroLot'] . '%');
        }
        if (!empty($filters['quartierId'])) {
            $qb->andWhere('q.id = :qid')->setParameter('qid', $filters['quartierId']);
        }
        if (!empty($filters['superficieMin'])) {
            $qb->andWhere('t.superficie >= :smin')->setParameter('smin', (float)$filters['superficieMin']);
        }
        if (!empty($filters['superficieMax'])) {
            $qb->andWhere('t.superficie <= :smax')->setParameter('smax', (float)$filters['superficieMax']);
        }
      
        // Tri (whitelist)
        $allowed = ['id', 'numero', 'numeroLot', 'superficie' , 'type'];
        $sf = in_array($sortField, $allowed, true) ? $sortField : 'id';
        $so = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy('t.' . $sf, $so);

        // Total
        $countQb = clone $qb;
        $total = (int) (clone $countQb)->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

        // Page
        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function create(array $data): TitreFoncier
    {
        $t = new TitreFoncier();
        $this->hydrate($t, $data);
        $this->em->persist($t);
        $this->em->flush();
        return $t;
    }

    public function update(TitreFoncier $t, array $data): TitreFoncier
    {
        $this->hydrate($t, $data);
        $this->em->flush();
        return $t;
    }

    public function delete(TitreFoncier $t): void
    {
        $this->em->remove($t);
        $this->em->flush();
    }

    private function hydrate(TitreFoncier $t, array $data): void
    {
        if (array_key_exists('numero', $data))       $t->setNumero($data['numero']);
        if (array_key_exists('superficie', $data))   $t->setSuperficie($data['superficie'] !== null ? (float)$data['superficie'] : null);
        if (array_key_exists('titreFigure', $data))  $t->setTitreFigure(is_array($data['titreFigure']) ? $data['titreFigure'] : null);
        if (array_key_exists('etatDroitReel', $data)) $t->setEtatDroitReel($data['etatDroitReel']);
        if (array_key_exists('numeroLot', $data))    $t->setNumeroLot($data['numeroLot']);
        if(array_key_exists('type', $data))           $t->setType($data['type']); $data['type'];

        if (array_key_exists('quartierId', $data)) {
            $q = null;
            if ($data['quartierId']) {
                $q = $this->em->getRepository(Localite::class)->find($data['quartierId']);
            }
            $t->setQuartier($q);
        }
    }
}
