<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\User;
use App\Service\UserOptionsService;
use App\Service\UserService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Spiral\Auth\AuthContextInterface;
use Spiral\Auth\Middleware\AuthMiddleware as Framework;
use Spiral\Translator\Traits\TranslatorTrait;

final class AuthMiddleware implements MiddlewareInterface
{
    use TranslatorTrait;

    const string HEADER_USER_ID = 'X-Internal-UserId';
    const string USER_LOCALE = 'X-Internal-UserLocale';

    public function __construct(
        private readonly UserService $userService,
        private readonly UserOptionsService $userOptionsService,
    ) {
    }

    #[\Override]
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

        $this->trackActiveAt($actor);

        return $handler->handle(
            $request->withAddedHeader(self::HEADER_USER_ID, (string) $actor->id)
                    ->withAttribute(self::USER_LOCALE, $this->userOptionsService->getLocale($actor))
        );
    }

    private function unauthenticated(): Response
    {
        return new JsonResponse([
            'message' => $this->say('error_authentication_required'),
        ], 401);
    }

    private function trackActiveAt(User $user): void
    {
        $user->activeAt = new \DateTimeImmutable();

        try {
            $this->userService->store($user);
        } catch (\Throwable) {
        }
    }
}
