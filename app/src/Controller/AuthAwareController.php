<?php

namespace App\Controller;

use App\Database\User;
use Spiral\Auth\AuthScope;

abstract class AuthAwareController
{
    /**
     * @var \App\Database\User
     */
    protected User $user;

    /**
     * ProfileStatisticsController constructor.
     *
     * @param \Spiral\Auth\AuthScope $auth
     */
    public function __construct(AuthScope $auth)
    {
        $user = $auth->getActor();

        if (! $user instanceof User) {
            return;
        }

        $this->user = $user;
    }
}
