<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\User;
use App\Service\UserOptionsService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Spiral\Auth\AuthContextInterface;
use Spiral\Auth\Middleware\AuthMiddleware as Framework;
use Spiral\Translator\Traits\TranslatorTrait;

class AuthMiddleware implements MiddlewareInterface
{
    use TranslatorTrait;

    const HEADER_USER_ID = 'X-Internal-UserId';
    const USER_LOCALE = 'X-Internal-UserLocale';

    public function __construct(
        private readonly UserOptionsService $userOptionsService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authContext = $request->getAttribute(Framework::ATTRIBUTE);

        if (! $authContext instanceof AuthContextInterface) {
            return $this->unauthenticated();
        }

        $actor = $authContext->getActor();

        if (! $actor instanceof User) {
            return $this->unauthenticated();
        }

        return $handler->handle(
            $request->withAddedHeader(self::HEADER_USER_ID, (string) $actor->id)
                    ->withAttribute(self::USER_LOCALE, $this->userOptionsService->getLocale($actor))
        );
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function unauthenticated(): Response
    {
        return new JsonResponse([
            'message' => $this->say('error_authentication_required'),
        ], 401);
    }
}
