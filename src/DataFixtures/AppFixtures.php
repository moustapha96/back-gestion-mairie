<?php

namespace App\DataFixtures;

use App\Entity\Article;
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
        $firstUser = new User();
        $secondUser = new User();

        $hashedPassword = $this->passwordHasher->hashPassword($firstUser, 'password');

        $firstUser->setUsername('First User');
        $firstUser->setPassword($hashedPassword);
        $firstUser->setRoles('ROLE_ADMIN');

        $secondUser->setUsername('Second User');
        $secondUser->setPassword($hashedPassword);
        $secondUser->setRoles('ROLE_ADMIN');

        $manager->persist($firstUser);
        $manager->persist($secondUser);



        $manager->flush();
    }
}
