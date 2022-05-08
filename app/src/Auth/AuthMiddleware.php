<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\User;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Spiral\Auth\AuthContextInterface;
use Spiral\Auth\Middleware\AuthMiddleware as Framework;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authContext = $request->getAttribute(Framework::ATTRIBUTE);

        if (! $authContext instanceof AuthContextInterface) {
            return $this->unauthenticated();
        }

        if (! $authContext->getActor() instanceof User) {
            return $this->unauthenticated();
        }

        return $handler->handle($request);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function unauthenticated(): Response
    {
        return new JsonResponse([
            'message' => 'Authentication required',
        ], 401);
    }
}
