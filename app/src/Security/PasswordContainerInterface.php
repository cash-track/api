<?php

declare(strict_types=1);

namespace App\Security;

interface PasswordContainerInterface
{
    /**
     * Return hashed password
     *
     * @return string
     */
    public function getPasswordHash(): string;

    /**
     * Set new hashed password
     *
     * @param string $password
     * @return void
     */
    public function setPasswordHash(string $password): void;
}