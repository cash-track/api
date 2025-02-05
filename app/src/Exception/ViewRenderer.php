<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Spiral\Http\ErrorHandler\RendererInterface;

final class ViewRenderer implements RendererInterface
{
    const MAP = [
        UnconfirmedProfileException::class => 403
    ];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function renderException(Request $request, int $code, \Throwable $exception): ResponseInterface
    {
        foreach (static::MAP as $className => $responseCode) {
            if ($exception instanceof $className) {
                return $this->renderJson($responseCode, $exception);
            }
        }

        return $this->renderJson($code, $exception);
    }

    private function renderJson(int $code, \Throwable $exception): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code);

        $response = $response->withHeader('Content-Type', 'application/json; charset=UTF-8');

        $payload = [
            'status' => $code,
            'error' => $exception->getMessage(),
        ];

        $response->getBody()->write((string) \json_encode($payload));

        return $response;
    }
}
