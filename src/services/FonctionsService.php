<?php

namespace App\services;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FonctionsService
{
    /**
     * Connexion vers la base "électeurs".
     * On injecte explicitement la connexion nommée "electeurs".
     */
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.electeurs_connection')]
        private readonly Connection $connElecteurs
    ) {}

    /**
     * Retourne TRUE si le NIN existe, FALSE sinon.
     */
    public function checkNumeroElecteurExist(?string $numeroElecteur): bool
    {
        if (!$numeroElecteur) {
            return false;
        }

        try {
            $sql = 'SELECT COUNT(*) AS c FROM electeurs2 WHERE NIN = :nin';
            $count = (int) $this->connElecteurs->fetchOne($sql, ['nin' => $numeroElecteur]);
            return $count > 0;
        } catch (\Throwable $e) {
            // Log raisonnable
            // logger PSR recommandé si dispo, sinon error_log
            error_log('checkNumeroElecteurExist error: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Récupère la ligne complète pour un NIN donné.
     * Retourne un tableau associatif ou null si introuvable.
     */
    public function fetchDataElecteur(string $numeroElecteur): ?array
    {
        try {
            $sql = 'SELECT * FROM electeurs2 WHERE NIN = :nin';
            $row = $this->connElecteurs->fetchAssociative($sql, ['nin' => $numeroElecteur]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('fetchDataElecteur error: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Recherche paginée.
     * Retourne ['items' => list<array>, 'total' => int]
     */
    public function fetchSearchPaginated(
        ?string $prenom,
        ?string $nom,
        ?string $email,
        ?string $telephone,
        ?string $profession,
        ?string $adresse,
        ?string $lieuNaissance,
        ?string $dateNaissance,
        ?string $numeroElecteur,
        int $page,
        int $pageSize
    ): array {
        // 1) WHERE + params
        $where = ' WHERE 1=1';
        $params = [];

        if ($nom) {
            $where .= ' AND NOM LIKE :nom';
            $params['nom'] = "%$nom%";
        }
        if ($prenom) {
            $where .= ' AND PRENOM LIKE :prenom';
            $params['prenom'] = "%$prenom%";
        }
        if ($numeroElecteur) {
            $where .= ' AND NIN = :nin';
            $params['nin'] = $numeroElecteur;
        }
        if ($telephone) {
            $where .= ' AND (TEL1 = :tel OR TEL2 = :tel OR WHATSAPP = :tel)';
            $params['tel'] = $telephone;
        }
        if ($email) {
            $where .= ' AND EMAIL = :email';
            $params['email'] = $email;
        }
        if ($profession) {
            $where .= ' AND PROFESSION LIKE :profession';
            $params['profession'] = "%$profession%";
        }
        if ($adresse) {
            $where .= ' AND ADRESSE LIKE :adresse';
            $params['adresse'] = "%$adresse%";
        }
        if ($lieuNaissance) {
            $where .= ' AND LIEU_NAISS LIKE :lieu';
            $params['lieu'] = "%$lieuNaissance%";
        }
        if ($dateNaissance) {
            $where .= ' AND DATE_NAISS = :dnaiss';
            $params['dnaiss'] = $dateNaissance;
        }

        // 2) Total
        $sqlCount = 'SELECT COUNT(*) FROM electeurs2'.$where;

        // 3) Items
        $offset = max(0, ($page - 1) * $pageSize);
        $limit  = max(1, $pageSize);

        $sqlItems = <<<SQL
            SELECT ID, CENTRE, BUREAU, NUMERO, NIN, PRENOM, NOM, DATE_NAISS, LIEU_NAISS,
                   PROFESSION, EN_ACTIVITE, ADRESSE, QUARTIER, WHATSAPP, TEL1, TEL2, EMAIL,
                   INSCRIT_AU_FICHIER, DEJA_VOTE, PARRAINAGE
              FROM electeurs2
              $where
             ORDER BY NOM ASC, PRENOM ASC
             LIMIT :limit OFFSET :offset
        SQL;

        try {
            $total = (int) $this->connElecteurs->fetchOne($sqlCount, $params);

            // DBAL n’autorise pas les :limit/:offset bindés en string, on force les types
            $stmt = $this->connElecteurs->prepare($sqlItems);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue('limit',  $limit,  \PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);

            $result = $stmt->executeQuery()->fetchAllAssociative();

            return ['items' => $result, 'total' => $total];
        } catch (\Throwable $e) {
            error_log('fetchSearchPaginated error: '.$e->getMessage());
            return ['items' => [], 'total' => 0];
        }
    }
}
