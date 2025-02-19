<?php

// src/EventListener/JWTCreatedListener.php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $payload = $event->getData();
        $user = $event->getUser();

        if ($user instanceof User) {
            // Ajoutez les informations de l'utilisateur
            $payload['roles'] = $user->getRoles();
            $payload['id'] = $user->getId();
            $payload['email'] = $user->getEmail();
            $payload['avatar'] = $user->getAvatar();
        }

        $event->setData($payload);
    }
}
