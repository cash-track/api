<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\User;
use Spiral\Auth\AuthScope;
use Spiral\Prototype\Traits\PrototypeTrait;

class Controller
{
    use PrototypeTrait;

    /**
     * @var \App\Database\User
     */
    protected $user;

    /**
     * WalletsActionsController constructor.
     *
     * @param \Spiral\Auth\AuthScope $auth
     */
    public function __construct(AuthScope $auth)
    {
        $user = $auth->getActor();

        if (! $user instanceof User) {
            throw new \RuntimeException('Unable to get authenticated user');
        }

        $this->user = $user;
    }
}
