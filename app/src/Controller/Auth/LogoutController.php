<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

class LogoutController
{
    use PrototypeTrait;

    /**
     * @Route(route="/auth/logout", name="auth.logout", methods="POST", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function logout(): ResponseInterface
    {
        $this->auth->close();

        // TODO. Optionally, this method should also add Token to the blacklist

        return $this->response->create(200);
    }
}
