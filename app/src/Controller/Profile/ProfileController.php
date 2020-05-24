<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Annotation\Route;
use App\Database\User;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;

class ProfileController
{
    use PrototypeTrait;

    /**
     * @Route(action="/profile", verbs={"GET"})
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(): ResponseInterface
    {
        $user = $this->auth->getActor();

        if (! $user instanceof User) {
            return $this->response->create(401);
        }

        return $this->response->json([
            'data' => [
                'id' => $user->id,
            ],
        ], 200);
    }
}
