<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

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
        $this->user = $auth->getActor();
    }
}
