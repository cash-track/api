<?php

namespace App\Security;

use App\Service\AuthService;
use Spiral\Validation\AbstractChecker;

class PasswordChecker extends AbstractChecker
{
    public const MESSAGES = [
        'verify' => 'Wrong password.'
    ];

    /**
     * @var \App\Service\AuthService
     */
    private $auth;

    /**
     * PasswordChecker constructor.
     *
     * @param \App\Service\AuthService $auth
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