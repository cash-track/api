<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Database\User;
use App\Security\PasswordContainerInterface;
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
     * AuthService constructor.
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
     * @param \App\Security\PasswordContainerInterface $container
     * @param string $password
     * @return void
     */
    public function hashPassword(PasswordContainerInterface $container, string $password): void
    {
        $container->setPasswordHash(password_hash($password, PASSWORD_ARGON2ID));
    }

    /**
     * @param \App\Security\PasswordContainerInterface $container
     * @param string $password
     * @return bool
     */
    public function verifyPassword(PasswordContainerInterface $container, string $password): bool
    {
        return password_verify($password, $container->getPasswordHash());
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
