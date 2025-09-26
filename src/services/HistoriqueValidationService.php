<?php

namespace App\services;

use App\Entity\DemandeTerrain;
use App\Entity\HistoriqueValidation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class HistoriqueValidationService
{
    public function __construct(private EntityManagerInterface $em) {}

    /** @return array{items: array<int,HistoriqueValidation>, total:int} */
    public function searchPaginated(array $filters, int $page, int $pageSize): array
    {
        $qb = $this->em->getRepository(HistoriqueValidation::class)->createQueryBuilder('h')
            ->leftJoin('h.demande', 'd')->addSelect('d')
            ->leftJoin('h.validateur', 'u')->addSelect('u');

        if (!empty($filters['demandeId'])) {
            $qb->andWhere('d.id = :did')->setParameter('did', (int)$filters['demandeId']);
        }
        if (!empty($filters['validateurId'])) {
            $qb->andWhere('u.id = :uid')->setParameter('uid', (int)$filters['validateurId']);
        }
        if (!empty($filters['action'])) {
            $qb->andWhere('h.action = :act')->setParameter('act', $filters['action']);
        }
        if (!empty($filters['from'])) {
            $qb->andWhere('h.dateAction >= :from')->setParameter('from', new \DateTime($filters['from']));
        }
        if (!empty($filters['to'])) {
            $qb->andWhere('h.dateAction <= :to')->setParameter('to', new \DateTime($filters['to']));
        }

        $qb->orderBy('h.dateAction', 'DESC');

        $total = (int)(clone $qb)->select('COUNT(h.id)')->getQuery()->getSingleScalarResult();

        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function create(array $data): HistoriqueValidation
    {
        $h = new HistoriqueValidation();

        $demande = $this->em->getRepository(DemandeTerrain::class)->find($data['demandeId'] ?? 0);
        $validateur = $this->em->getRepository(User::class)->find($data['validateurId'] ?? 0);

        if (!$demande || !$validateur) {
            throw new \InvalidArgumentException('demandeId ou validateurId invalide');
        }
        $h->setDemande($demande);
        $h->setValidateur($validateur);
        $h->setAction($data['action'] ?? 'validé'); // validé | rejeté
        $h->setMotif($data['motif'] ?? null);
        if (!empty($data['dateAction'])) {
            $h->setDateAction(new \DateTime($data['dateAction']));
        }

        $this->em->persist($h);
        $this->em->flush();
        return $h;
    }

    public function delete(HistoriqueValidation $h): void
    {
        $this->em->remove($h);
        $this->em->flush();
    }
}
