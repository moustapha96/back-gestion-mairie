<?php


namespace App\services;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FonctionsService extends AbstractController
{

    private function pdo(): \PDO
    {
        return new \PDO(
            'mysql:host=localhost:3306;dbname=c2616155c_evyr_elections2;charset=utf8mb4',
            'root',
            '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }


    public function checkNumeroElecteurExist(string $numeroElecteur = null): bool
    {
        if ($numeroElecteur === null) {
            return false;
        }
        try {

            // $user = "c2616155c_glotissement";
            // $mdp = "Ccbm@2025";
            // $db = "c2616155c_evyr_elections2";
            // $host = "https://91.234.194.249:2083/";
            // Connexion à la base de données
            $pdo = new \PDO(
                'mysql:host=localhost:3306;dbname=c2616155c_evyr_elections2',
                'root',
                '',
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
            );
            // $pdo = new \PDO(
            //     'mysql:host=localhost;dbname=c2616155c_evyr_elections2',
            //     $user,
            //     $mdp,
            //     array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
            // );
            // Préparation de la requête
            $query = "SELECT COUNT(*) FROM electeurs2 WHERE NIN = :numeroElecteur";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['numeroElecteur' => $numeroElecteur]);
            // Récupération du résultat
            $count = (int) $stmt->fetchColumn();
            return $count > 0;
        } catch (\PDOException $e) {
            // En cas d'erreur, on log l'erreur et on retourne false
            error_log("Erreur lors de la vérification du numéro d'électeur : " . $e->getMessage());
            return false;
        }
    }

    public function fetchDataElecteur(string $numeroElecteur): ?array
    {
        try {
            // Connexion à la base de données
            $pdo = new \PDO(
                'mysql:host=localhost;port=3306;dbname=c2616155c_evyr_elections2;charset=utf8mb4',
                'root',
                '',
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
            );

            $query = "SELECT * FROM electeurs2 WHERE NIN = :numeroElecteur";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':numeroElecteur' => $numeroElecteur]);

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result !== false ? $result : null;
        } catch (\PDOException $e) {
            // Log de l'erreur
            error_log("Erreur lors de la récupération des données de l'électeur : " . $e->getMessage());
            return null;
        }
    }

    public function fetchSearch(
        ?string $prenom,
        ?string $nom,
        ?string $email,
        ?string $telephone,
        ?string $profession,
        ?string $adresse,
        ?string $lieuNaissance,
        ?string $dateNaissance,
        ?string $numeroElecteur,
        $page,
        $pageSize
    ): ?array {
        try {
            // Connexion à la base de données
            $pdo = new \PDO(
                'mysql:host=localhost;port=3306;dbname=evyr_elections2;charset=utf8mb4',
                'root',
                '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Construction de la requête SQL
            $query = "SELECT * FROM electeurs2 WHERE ";
            $conditions = [];
            $where  = " WHERE 1=1";
            $params = [];

            // // Ajout des conditions en fonction des paramètres fournis
            // if ($prenom !== null) {
            //     $conditions[] = "PRENOM LIKE :prenom";
            //     $params[':prenom'] = '%' . $prenom . '%';
            // }
            // if ($nom !== null) {
            //     $conditions[] = "NOM LIKE :nom";
            //     $params[':nom'] = '%' . $nom . '%';
            // }
            // if ($email !== null) {
            //     $conditions[] = "EMAIL LIKE :email";
            //     $params[':email'] = '%' . $email . '%';
            // }
            // if ($telephone !== null) {
            //     $conditions[] = "(TEL1 LIKE :telephone OR TEL2 LIKE :telephone)";
            //     $params[':telephone'] = '%' . $telephone . '%';
            // }
            // if ($profession !== null) {
            //     $conditions[] = "PROFESSION LIKE :profession";
            //     $params[':profession'] = '%' . $profession . '%';
            // }
            // if ($adresse !== null) {
            //     $conditions[] = "ADRESSE LIKE :adresse";
            //     $params[':adresse'] = '%' . $adresse . '%';
            // }
            // if ($lieuNaissance !== null) {
            //     $conditions[] = "LIEU_NAISS = :lieuNaissance";
            //     $params[':lieuNaissance'] = $lieuNaissance;
            // }
            // if ($dateNaissance !== null) {
            //     $conditions[] = "DATE_NAISS = :dateNaissance";
            //     $params[':dateNaissance'] = $dateNaissance;
            // }
            // if ($numeroElecteur !== null) {
            //     $conditions[] = "nin LIKE :numeroElecteur";
            //     $params[':numeroElecteur'] =  '%' . $numeroElecteur . '%';
            // }

            if ($nom) {
                $where .= " AND NOM LIKE :nom";
                $params[':nom'] = "%$nom%";
            }
            if ($prenom) {
                $where .= " AND PRENOM LIKE :prenom";
                $params[':prenom'] = "%$prenom%";
            }
            if ($numeroElecteur) {
                $where .= " AND NIN = :nin";
                $params[':nin'] = $numeroElecteur;
            }
            if ($telephone) {
                $where .= " AND (TEL1 = :tel OR TEL2 = :tel OR WHATSAPP = :tel)";
                $params[':tel'] = $telephone;
            }
            if ($email) {
                $where .= " AND EMAIL = :email";
                $params[':email'] = $email;
            }
            if ($profession) {
                $where .= " AND PROFESSION LIKE :profession";
                $params[':profession'] = "%$profession%";
            }
            if ($adresse) {
                $where .= " AND ADRESSE LIKE :adresse";
                $params[':adresse'] = "%$adresse%";
            }
            if ($lieuNaissance) {
                $where .= " AND LIEU_NAISS LIKE :lieu";
                $params[':lieu'] = "%$lieuNaissance%";
            }
            if ($dateNaissance) {
                $where .= " AND DATE_NAISS = :dnaiss";
                $params[':dnaiss'] = $dateNaissance;
            }


            $sqlCount = "SELECT COUNT(*) AS c FROM electeurs2" . $where;
            $stc = $pdo->prepare($sqlCount);
            foreach ($params as $k => $v) $stc->bindValue($k, $v);
            $stc->execute();
            $total = (int)$stc->fetchColumn();

            $offset = ($page - 1) * $pageSize;
            $sql = "SELECT ID, CENTRE, BUREAU, NUMERO, NIN, PRENOM, NOM, DATE_NAISS, LIEU_NAISS,
                       PROFESSION, EN_ACTIVITE, ADRESSE, QUARTIER, WHATSAPP, TEL1, TEL2, EMAIL,
                       INSCRIT_AU_FICHIER, DEJA_VOTE, PARRAINAGE
                FROM electeurs2"
                . $where .
                " ORDER BY NOM ASC, PRENOM ASC
                 LIMIT :limit OFFSET :offset";

            // Combinaison des conditions avec "AND"
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit',  $pageSize, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset,   \PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return ['items' => $items, 'total' => $total];
        } catch (\PDOException $e) {
            // Log de l'erreur
            error_log("Erreur lors de la recherche des données de l'électeur : " . $e->getMessage());
            return null;
        }
    }

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
        $pdo = $this->pdo();

        // 1) Build WHERE + params (réutilisable)
        $where  = " WHERE 1=1";
        $params = [];

        if ($nom) {
            $where .= " AND NOM LIKE :nom";
            $params[':nom'] = "%$nom%";
        }
        if ($prenom) {
            $where .= " AND PRENOM LIKE :prenom";
            $params[':prenom'] = "%$prenom%";
        }
        if ($numeroElecteur) {
            $where .= " AND NIN = :nin";
            $params[':nin'] = $numeroElecteur;
        }
        if ($telephone) {
            $where .= " AND (TEL1 = :tel OR TEL2 = :tel OR WHATSAPP = :tel)";
            $params[':tel'] = $telephone;
        }
        if ($email) {
            $where .= " AND EMAIL = :email";
            $params[':email'] = $email;
        }
        if ($profession) {
            $where .= " AND PROFESSION LIKE :profession";
            $params[':profession'] = "%$profession%";
        }
        if ($adresse) {
            $where .= " AND ADRESSE LIKE :adresse";
            $params[':adresse'] = "%$adresse%";
        }
        if ($lieuNaissance) {
            $where .= " AND LIEU_NAISS LIKE :lieu";
            $params[':lieu'] = "%$lieuNaissance%";
        }
        if ($dateNaissance) {
            $where .= " AND DATE_NAISS = :dnaiss";
            $params[':dnaiss'] = $dateNaissance;
        }

        // 2) Total
        $sqlCount = "SELECT COUNT(*) AS c FROM electeurs2" . $where;
        $stc = $pdo->prepare($sqlCount);
        foreach ($params as $k => $v) $stc->bindValue($k, $v);
        $stc->execute();
        $total = (int)$stc->fetchColumn();

        // 3) Page de résultats
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT ID, CENTRE, BUREAU, NUMERO, NIN, PRENOM, NOM, DATE_NAISS, LIEU_NAISS,
                       PROFESSION, EN_ACTIVITE, ADRESSE, QUARTIER, WHATSAPP, TEL1, TEL2, EMAIL,
                       INSCRIT_AU_FICHIER, DEJA_VOTE, PARRAINAGE
                FROM electeurs2"
            . $where .
            " ORDER BY NOM ASC, PRENOM ASC
                 LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $pageSize, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return ['items' => $items, 'total' => $total];
    }
}
