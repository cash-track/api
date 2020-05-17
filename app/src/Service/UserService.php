<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\User;
use Cycle\ORM\ORM;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="userService")
 */
class UserService
{
    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    private $tr;

    /**
     * @var \Cycle\ORM\ORM
     */
    private $orm;

    /**
     * UserService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     */
    public function __construct(TransactionInterface $tr, ORM $orm)
    {
        $this->tr = $tr;
        $this->orm = $orm;
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

    /**
     * @param \App\Database\User $user
     * @param string $password
     * @return \App\Database\User
     */
    public function hashPassword(User $user, string $password): User
    {
        $user->password = password_hash($password, PASSWORD_ARGON2ID);

        return $user;
    }
}
