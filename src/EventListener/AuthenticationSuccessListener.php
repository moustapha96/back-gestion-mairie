<?php

// src/EventListener/AuthenticationSuccessListener.php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class AuthenticationSuccessListener
{
    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }
        if ($user instanceof User && !$user->isActiveted()) {
            $event->setData(['code' => 403, 'status' => "Votre Compte n'est pas actif"]);
            return;
        }

        if ($user instanceof User) {

            $demandes = [];
            if ($user->getDemandes()->count() > 0) {
                foreach ($user->getDemandes() as $value) {
                    $demandes[] = $value->toArray();
                }
            }

            $roles = in_array('ROLE_SUPER_ADMIN', $user->getRoles()) ? $user->getRoles() : $user->getRoles()[0];

            $data['user'] = [
                'roles' => $roles,
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'activated' => $user->isActiveted(),
                'avatar' => $user->getAvatar(),
                'username' => $user->getUsername(),
                'prenom' => $user->getPrenom(),
                'nom' => $user->getNom(),
                "adresse" => $user->getAdresse(),
                'profession' => $user->getProfession(),
                'telephone' => $user->getTelephone(),
                'lieuNaissance' => $user->getLieuNaissance(),
                'dateNaissance' => $user->getDateNaissance(),
                'numeroElecteur' => $user->getNumeroElecteur(),
                'signature' => $user->getSignature() ? $user->getSignature()->toArray() : null,
                'demande' => $demandes,
                'habitant' => $user->isHabitant(),
            ];
        }

        $event->setData($data);
    }
}




// <?php

// // src/EventListener/AuthenticationSuccessListener.php

// namespace App\EventListener;

// use App\Entity\User;
// use Symfony\Component\Security\Core\User\UserInterface;
// use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

// class AuthenticationSuccessListener
// {
//     /**
//      * @param AuthenticationSuccessEvent $event
//      */
//     public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
//     {
//         $data = $event->getData();
//         $user = $event->getUser();

//         if (!$user instanceof UserInterface) {
//             return;
//         }

//         if ($user instanceof User && !$user->isActiveted()) {
//             $event->setData(['code' => 403, 'status' => "Votre compte n'est pas actif."]);
//             return;
//         }

//         if ($user instanceof User) {
//             $data['user'] = [
//                 'id' => $user->getId(),
//                 'username' => $user->getUsername(),
//                 'email' => $user->getEmail(),
//                 'roles' => $user->getRoles(),
//                 'avatar' => $user->getAvatar(),
//                 'prenom' => $user->getPrenom(),
//                 'nom' => $user->getNom(),
//                 'adresse' => $user->getAdresse(),
//                 'profession' => $user->getProfession(),
//                 'telephone' => $user->getTelephone(),
//                 'lieuNaissance' => $user->getLieuNaissance(),
//                 'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
//                 'numeroElecteur' => $user->getNumeroElecteur(),
//                 'signature' => $user->getSignature()?->toArray(),
//                 'demandes' => array_map(fn($demande) => $demande->toArray(), $user->getDemandes()->toArray()),
//             ];
//         }

//         $event->setData($data);
//     }
// }
