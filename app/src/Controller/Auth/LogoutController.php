<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LogoutRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class LogoutController
{
    use PrototypeTrait;

    /**
     * @Route(route="/auth/logout", name="auth.logout", methods="POST", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function logout(LogoutRequest $request): ResponseInterface
    {
        $this->auth->close();

        $refreshToken = $this->refreshTokenService->getContextByToken($request->getRefreshToken())->getToken();

        if ($refreshToken instanceof TokenInterface) {
            $this->refreshTokenService->close($refreshToken);
        }

        return $this->response->create(200);
    }
}
