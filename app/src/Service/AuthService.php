<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\User;
use Spiral\Auth\AuthScope;
use Spiral\Auth\TokenInterface;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="authService")
 */
class AuthService
{
    /**
     * @var \Spiral\Auth\AuthScope
     */
    private $auth;

    /**
     * @var \Spiral\Auth\TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * UserService constructor.
     *
     * @param \Spiral\Auth\AuthScope $auth
     * @param \Spiral\Auth\TokenStorageInterface $tokenStorage
     */
    public function __construct(AuthScope $auth, TokenStorageInterface $tokenStorage)
    {
        $this->auth = $auth;
        $this->tokenStorage = $tokenStorage;
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

    /**
     * @param \App\Database\User $user
     * @param string $password
     * @return bool
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return password_verify($password, $user->password);
    }

    /**
     * @param \App\Database\User $user
     * @return \Spiral\Auth\TokenInterface
     */
    public function authenticate(User $user): TokenInterface
    {
        $token = $this->tokenStorage->create([
            'sub' => $user->id,
        ]);

        $this->auth->start($token);

        return $token;
    }
}
