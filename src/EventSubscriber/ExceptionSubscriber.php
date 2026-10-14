<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            // 404 (route ou entité introuvable), 403 (pas ADMIN), 405 (mauvais verbe)...
            $status = $exception->getStatusCode();
            $message = $exception->getMessage();
        } elseif ($exception instanceof SerializerExceptionInterface) {
            // JSON mal formé ou mauvais type de donnée envoyé dans le body
            $status = Response::HTTP_BAD_REQUEST;
            $message = $exception->getMessage();
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = $exception->getMessage();
        }

        $event->setResponse(new JsonResponse([
            'status' => $status,
            'message' => $message,
        ], $status));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
