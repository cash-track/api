<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\User;
use Cycle\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(private readonly EntityManagerInterface $tr)
    {
    }

    public function store(User $user): User
    {
        $this->tr->persist($user);
        $this->tr->run();

        return $user;
    }
}
