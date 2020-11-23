<?php

namespace Lturi\SymfonyExtensions\Framework\Service;

use Exception;
use Lturi\SymfonyExtensions\Framework\Constants;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class HttpExceptionService
{
    /** @var RouterInterface */
    protected $router;
    /** @var ContainerInterface */
    protected $container;
    /** @var string */
    protected $apiPathSegment;

    public function __construct(RouterInterface $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->container = $container;
        $this->apiPathSegment = $container->getParameter(Constants::API_PATH);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundHttpException) {
            if (strpos($event->getRequest()->getRequestUri(), $this->apiPathSegment) !== false) {
                $response = $this->errorJson($exception);
            } else {
                $response = $this->error($exception, "4xx");
            }
            $this->setResponse($event, $response, $exception);
        } elseif ($exception instanceof HttpException) {
            if (strpos($event->getRequest()->getRequestUri(), $this->apiPathSegment) !== false) {
                $response = $this->errorJson($exception);
            } else {
                $response = $this->error($exception);
            }
            $this->setResponse($event, $response, $exception);
        } elseif ($exception instanceof Exception) {
            if (isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"], $this->apiPathSegment) !== false) {
                $response = $this->errorJson($exception);
            } else {
                $response = $this->error($exception);
            }
            $this->setResponse($event, $response, $exception);
        }
    }

    private function setResponse(ExceptionEvent $event, Response $response, Exception $exception): void
    {
        if ($exception instanceof HttpException) {
            $response->setStatusCode($exception->getStatusCode());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $event->setResponse($response);
    }

    public function error(Exception $exception, string $template = "5xx"): Response
    {
        // TODO: check it works -> added bundle prefix. Need to move here templates and assets.
        $content = $this->container->get('twig')->render(sprintf('@LturiSymfonyExtensions/exception/%s.html.twig', $template), [
            'exception' => $exception,
        ]);
        $response = new Response();
        $response->setContent($content);
        return $response;
    }
    public function errorJson(Exception $exception): Response
    {
        return new JsonResponse([
            'success' => false,
            'code' => $exception instanceof HttpException ? $exception->getStatusCode() : 500,
            'message' => $exception->getMessage(),
            'stacktrace' => $exception->getTrace()
        ]);
    }
}
