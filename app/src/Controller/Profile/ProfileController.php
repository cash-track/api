<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

class ProfileController
{
    use PrototypeTrait;

    /**
     * @Route(route="/profile", name="profile.index", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        return $this->response->json([
            'data' => [
                'id' => $user->id,
            ],
        ], 200);
    }
}
