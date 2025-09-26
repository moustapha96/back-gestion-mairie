<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    /**
     * @param array{
     *   page?:int, size?:int, search?:string|null, role?:string|null,
     *   enabled?:bool|null, activated?:bool|null,
     *   sort?:string|null // "id,DESC" | "nom,ASC" | "email,DESC"
     * } $criteria
     * @return array{items: list<User>, total:int, page:int, size:int, pages:int}
     */
    public function searchPaginated(array $criteria): array
    {
        $page  = max(1, (int)($criteria['page'] ?? 1));
        $size  = min(max((int)($criteria['size'] ?? 10), 1), 100);
        [$sf, $sd] = array_pad(explode(',', (string)($criteria['sort'] ?? 'id,DESC'), 2), 2, 'DESC');

        $sortable = ['id','nom','prenom','email','username'];
        $sf = \in_array($sf, $sortable, true) ? $sf : 'id';
        $sd = strtoupper($sd) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('u');

        // Recherche plein texte simple
        if (($s = trim((string)($criteria['search'] ?? ''))) !== '') {
            $qb->andWhere('
                LOWER(u.nom) LIKE :s OR LOWER(u.prenom) LIKE :s OR
                LOWER(u.email) LIKE :s OR LOWER(u.username) LIKE :s OR
                LOWER(u.telephone) LIKE :s
            ')
            ->setParameter('s', '%'.mb_strtolower($s).'%');
        }

        // Filtre rôle (roles est un tableau JSON) — Doctrine supporte MEMBER OF
        if (!empty($criteria['role'])) {
            $qb->andWhere(':role MEMBER OF u.roles')
               ->setParameter('role', $criteria['role']);
        }

        if (array_key_exists('enabled', $criteria) && $criteria['enabled'] !== null) {
            $qb->andWhere('u.enabled = :en')->setParameter('en', (bool)$criteria['enabled']);
        }
        if (array_key_exists('activated', $criteria) && $criteria['activated'] !== null) {
            $qb->andWhere('u.activeted = :ac')->setParameter('ac', (bool)$criteria['activated']);
        }

        // Tri
        $qb->orderBy('u.'.$sf, $sd);

        // Total
        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        // Pagination
        $qb->setFirstResult(($page - 1) * $size)->setMaxResults($size);
        $items = $qb->getQuery()->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'size'  => $size,
            'pages' => (int)ceil($total / $size),
        ];
    }
}
