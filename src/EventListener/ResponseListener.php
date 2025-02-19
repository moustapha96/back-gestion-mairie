<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;

class ResponseListener
{
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        // Force la réponse en JSON uniquement pour les routes API
        if (strpos($request->getPathInfo(), '/api') === 0) {
            $data = $event->getControllerResult();
            $event->setResponse(new JsonResponse($data, Response::HTTP_OK, ['Content-Type' => 'application/json']));
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Vérifie si la requête concerne l'API et si la réponse n'est pas déjà en JSON
        if (strpos($request->getPathInfo(), '/api') === 0) {
            $response = $event->getResponse();

            if (!$response instanceof JsonResponse) {
                $content = $response->getContent();
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $event->setResponse(new JsonResponse($data, $response->getStatusCode(), ['Content-Type' => 'application/json']));
                }
            }
        }
    }
}
