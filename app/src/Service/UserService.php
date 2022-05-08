<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\User;
use Cycle\ORM\EntityManagerInterface;

class UserService
{
    /**
     * @var \Cycle\ORM\EntityManagerInterface
     */
    private $tr;

    /**
     * UserService constructor.
     *
     * @param \Cycle\ORM\EntityManagerInterface $tr
     */
    public function __construct(EntityManagerInterface $tr)
    {
        $this->tr = $tr;
    }

    /**
     * @param \App\Database\User $user
     * @return \App\Database\User
     * @throws \Throwable
     */
    public function store(User $user): User
    {
        $this->tr->persist($user);
        $this->tr->run();

        return $user;
    }
}
