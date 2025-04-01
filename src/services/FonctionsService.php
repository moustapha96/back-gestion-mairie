<?php


namespace App\services;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FonctionsService extends AbstractController
{

    public function checkNumeroElecteurExist(string $numeroElecteur = null): bool
    {
        if ($numeroElecteur === null) {
            return false;
        }
        try {
            // Connexion à la base de données
            $pdo = new \PDO(
                'mysql:host=localhost:3306;dbname=evyr_elections2',
                'root',
                '',
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
            );

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
                'mysql:host=localhost;port=3306;dbname=evyr_elections2;charset=utf8mb4',
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
}
