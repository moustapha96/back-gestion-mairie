<?php

namespace App\services;

use App\Entity\Request as Demande;
use App\Entity\HistoriqueValidation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class HistoriqueValidationService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * @return array{items: array<int,HistoriqueValidation>, total:int}
     */
    public function searchPaginated(array $filters, int $page, int $pageSize): array
    {
        $qb = $this->em->getRepository(HistoriqueValidation::class)->createQueryBuilder('h')
            ->leftJoin('h.request', 'd')->addSelect('d')
            ->leftJoin('h.validateur', 'u')->addSelect('u');

        // Filtres standards
        if (!empty($filters['demandeId'])) {
            $qb->andWhere('d.id = :did')->setParameter('did', (int) $filters['demandeId']);
        }
        if (!empty($filters['validateurId'])) {
            $qb->andWhere('u.id = :uid')->setParameter('uid', (int) $filters['validateurId']);
        }
        if (!empty($filters['action'])) {
            // normalisation "valide"|"rejete"
            $act = strtolower(trim($filters['action']));
            if (in_array($act, ['valide', 'rejete'], true)) {
                $qb->andWhere('LOWER(h.action) = :act')->setParameter('act', $act);
            }
        }
        if (!empty($filters['from'])) {
            $qb->andWhere('h.dateAction >= :from')->setParameter('from', new \DateTime($filters['from']));
        }
        if (!empty($filters['to'])) {
            $qb->andWhere('h.dateAction <= :to')->setParameter('to', new \DateTime($filters['to']));
        }

        // Filtres snapshots (si colonnes présentes)
        if (!empty($filters['niveauOrdre']) && $this->hasField(HistoriqueValidation::class, 'niveauOrdre')) {
            $qb->andWhere('h.niveauOrdre = :nOrdre')->setParameter('nOrdre', (int) $filters['niveauOrdre']);
        }
        if (!empty($filters['niveauNom']) && $this->hasField(HistoriqueValidation::class, 'niveauNom')) {
            $qb->andWhere('h.niveauNom = :nNom')->setParameter('nNom', $filters['niveauNom']);
        }
        if (!empty($filters['roleRequis']) && $this->hasField(HistoriqueValidation::class, 'roleRequis')) {
            $qb->andWhere('h.roleRequis = :rReq')->setParameter('rReq', $filters['roleRequis']);
        }
        if (!empty($filters['statutAvant']) && $this->hasField(HistoriqueValidation::class, 'statutAvant')) {
            $qb->andWhere('h.statutAvant = :sAvant')->setParameter('sAvant', $filters['statutAvant']);
        }
        if (!empty($filters['statutApres']) && $this->hasField(HistoriqueValidation::class, 'statutApres')) {
            $qb->andWhere('h.statutApres = :sApres')->setParameter('sApres', $filters['statutApres']);
        }

        $qb->orderBy('h.dateAction', 'DESC');

        $total = (int) (clone $qb)->select('COUNT(h.id)')->getQuery()->getSingleScalarResult();

        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()->getResult();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Crée un historique à partir d’un payload “front”.
     *
     * Payload attendu :
     *  - demande           : int (id)
     *  - validateurId      : int (id)
     *  - action            : "valide" | "rejete"
     *  - motif?            : string
     *  - dateAction?       : string (ISO/Y-m-d H:i:s)
     *  - niveauNom?        : string
     *  - niveauOrdre?      : int
     *  - roleRequis?       : string
     *  - statutAvant?      : string
     *  - statutApres?      : string
     */
    public function create(array $data): HistoriqueValidation
    {
        // Normalisation / validation d’entrée
        $demandeId    = $data['demande'] ?? null;       // ⚠️ nom "demande" (pas demandeId)
        $validateurId = $data['validateurId'] ?? null;
        $actionRaw    = strtolower(trim($data['action'] ?? ''));

        if (!$demandeId || !$validateurId) {
            throw new \InvalidArgumentException('Champs "demande" et "validateurId" sont requis.');
        }
        if (!in_array($actionRaw, ['valide', 'rejete'], true)) {
            throw new \InvalidArgumentException('Le champ "action" doit être "valide" ou "rejete".');
        }

        $demande = $this->em->getRepository(Demande::class)->find((int) $demandeId);
        if (!$demande) {
            throw new \InvalidArgumentException('Demande introuvable.');
        }
        $validateur = $this->em->getRepository(User::class)->find((int) $validateurId);
        if (!$validateur) {
            throw new \InvalidArgumentException('Validateur introuvable.');
        }

        // Création
        $h = new HistoriqueValidation();
        $h->setRequest($demande);
        $h->setValidateur($validateur);
        $h->setAction($actionRaw); // "valide"|"rejete"
        $h->setMotif($data['motif'] ?? null);

        if (!empty($data['dateAction'])) {
            $h->setDateAction(new \DateTime($data['dateAction']));
        } // sinon, le __construct() de l’entité met déjà "maintenant"

        // Snapshots (si colonnes ajoutées dans l’entité)
        if (method_exists($h, 'setNiveauNom'))   $h->setNiveauNom($data['niveauNom']   ?? null);
        if (method_exists($h, 'setNiveauOrdre')) $h->setNiveauOrdre($data['niveauOrdre'] ?? null);
        if (method_exists($h, 'setRoleRequis'))  $h->setRoleRequis($data['roleRequis']  ?? null);
        if (method_exists($h, 'setStatutAvant')) $h->setStatutAvant($data['statutAvant'] ?? null);
        if (method_exists($h, 'setStatutApres')) $h->setStatutApres($data['statutApres'] ?? null);

        $this->em->persist($h);
        $this->em->flush();

        return $h;
    }

    public function delete(HistoriqueValidation $h): void
    {
        $this->em->remove($h);
        $this->em->flush();
    }

    /** Vérifie si le champ simple (non-relation) existe dans les métadonnées Doctrine. */
    private function hasField(string $class, string $field): bool
    {
        $meta = $this->em->getClassMetadata($class);
        return $meta->hasField($field);
    }
}
