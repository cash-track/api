<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Database\User;
use Spiral\Auth\TokenInterface;

final class Authentication
{
    public function __construct(
        public User $user,
        public TokenInterface $accessToken,
        public TokenInterface $refreshToken,
    ) {
    }
}
