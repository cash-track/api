<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\Auth\AuthService;
use Cycle\ORM\ORMInterface;
use Spiral\Validator\AbstractChecker;

class PasswordChecker extends AbstractChecker
{
    public const MESSAGES = [
        'verify' => 'password_verify_error',
    ];

    public function __construct(
        private AuthService $auth,
        private ORMInterface $orm
    ) {
    }

    public function verify(mixed $value, string $role, string $field = 'id'): bool
    {
        if (!$this->getValidator()->hasValue($field) || !class_exists($role)) {
            return false;
        }

        /** @var \Cycle\ORM\Select\Repository $repository */
        $repository = $this->orm->getRepository($role);

        $select = $repository->select();
        $select->where($field, $this->getValidator()->getValue($field));
        $entity = $select->fetchOne();

        if (! $entity instanceof PasswordContainerInterface) {
            return false;
        }

        return $this->auth->verifyPassword($entity, $value);
    }
}
