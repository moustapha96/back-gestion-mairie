<?php


namespace App\services;

use App\Entity\DemandeTerrain;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class FonctionsService extends AbstractController
{

    private function checkNumeroElecteurExist(string $numeroElecteur): bool
    {
        try {
            // Connexion à la base de données
            $pdo = new \PDO(
                'mysql:host=localhost:3306;dbname=adn_db_actif',
                'root',
                '',
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
            );

            // Préparation de la requête
            $query = "SELECT COUNT(*) FROM adn_users WHERE numero_electeur = :numeroElecteur";
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
}
