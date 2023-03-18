<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\Auth\AuthService;
use Spiral\Validator\AbstractChecker;

class PasswordChecker extends AbstractChecker
{
    public const MESSAGES = [
        'verify' => 'Wrong password.'
    ];

    /**
     * @var \App\Service\Auth\AuthService
     */
    private $auth;

    /**
     * PasswordChecker constructor.
     *
     * @param \App\Service\Auth\AuthService $auth
     */
    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Expect an instance of PasswordContainerInterface in request context.
     *
     * @param mixed $value
     * @return bool
     */
    public function verify($value): bool
    {
        $entity = $this->getValidator()->getContext();
        if (! $entity instanceof PasswordContainerInterface) {
            return false;
        }

        return $this->auth->verifyPassword($entity, $value);
    }
}
