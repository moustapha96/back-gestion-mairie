<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtLoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {

       
    }
}
