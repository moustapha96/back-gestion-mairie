<?php

namespace App\services;

use App\Entity\NiveauValidation;
use App\Repository\NiveauValidationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NiveauValidationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NiveauValidationRepository $repo
    ) {}

    /** @return array{items: array<int,NiveauValidation>, total:int} */
    public function searchPaginated(array $filters, int $page, int $pageSize): array
    {
        $qb = $this->repo->createQueryBuilder('n');

        if (!empty($filters['nom'])) {
            $qb->andWhere('n.nom LIKE :nom')->setParameter('nom', '%' . $filters['nom'] . '%');
        }
        if (!empty($filters['roleRequis'])) {
            $qb->andWhere('n.roleRequis = :role')->setParameter('role', $filters['roleRequis']);
        }
        if (!empty($filters['ordreMin'])) {
            $qb->andWhere('n.ordre >= :omin')->setParameter('omin', (int)$filters['ordreMin']);
        }
        if (!empty($filters['ordreMax'])) {
            $qb->andWhere('n.ordre <= :omax')->setParameter('omax', (int)$filters['ordreMax']);
        }

        $qb->orderBy('n.ordre', 'ASC');

        $total = (int)(clone $qb)->select('COUNT(n.id)')->getQuery()->getSingleScalarResult();

        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function create(array $data): NiveauValidation
    {
        $n = new NiveauValidation();
        $this->hydrate($n, $data);
        $this->em->persist($n);
        $this->em->flush();
        return $n;
    }

    public function update(NiveauValidation $n, array $data): NiveauValidation
    {
        $this->hydrate($n, $data);
        $this->em->flush();
        return $n;
    }

    public function delete(NiveauValidation $n): void
    {
        $this->em->remove($n);
        $this->em->flush();
    }

    private function hydrate(NiveauValidation $n, array $data): void
    {
        if (array_key_exists('nom', $data))        $n->setNom($data['nom']);
        if (array_key_exists('roleRequis', $data)) $n->setRoleRequis($data['roleRequis']);
        if (array_key_exists('ordre', $data))      $n->setOrdre($data['ordre'] !== null ? (int)$data['ordre'] : null);
    }
}
