<?php
namespace App\EventListener;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ApiException) {
            $data     = json_decode($exception->getMessage(), true);
            $response = new JsonResponse($data, $exception->getStatusCode());
            $event->setResponse($response);
        }
    }
}
