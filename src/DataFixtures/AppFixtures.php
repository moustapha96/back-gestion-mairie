<?php


// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Mot de passe par défaut pour tous les utilisateurs
        $defaultPassword = 'password123';
        $hashedPassword = $this->passwordHasher->hashPassword(new User(), $defaultPassword);

        // Liste des rôles disponibles
        $roles = [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_MAIRE,
            User::ROLE_CHEF_SERVICE,
            User::ROLE_PRESIDENT_COMMISSION,
            User::ROLE_PERCEPTEUR,
            User::ROLE_AGENT,
            User::ROLE_DEMANDEUR,
        ];

        // Création d'un utilisateur pour chaque rôle
        foreach ($roles as $index => $role) {
            $user = new User();
            $username = 'user_' . strtolower(str_replace('ROLE_', '', $role));
            $email = $username . '@example.com';

            $user->setUsername($username);
            $user->setEmail($email);
            $user->setPassword($hashedPassword);
            $user->setRoles($role);
            $user->setPrenom('Prénom ' . ($index + 1));
            $user->setNom('Nom ' . ($index + 1));
            $user->setDateNaissance(new \DateTime('1990-01-01'));
            $user->setLieuNaissance('Dakar');
            $user->setAdresse('Adresse ' . ($index + 1));
            $user->setProfession('Profession ' . ($index + 1));
            $user->setTelephone('77000000' . ($index + 1));
            $user->setNumeroElecteur('NE' . str_pad($index + 1, 5, '0', STR_PAD_LEFT));
            $user->setEnabled(true);
            $user->setActiveted(true);

            $manager->persist($user);
            $this->addReference($role, $user);
        }

        $manager->flush();
    }
}
