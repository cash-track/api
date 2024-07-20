<?php

declare(strict_types=1);

namespace App\Security;

use App\Database\Encrypter\EncrypterInterface;
use Cycle\ORM\ORMInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Cycle\Validation\EntityChecker;

#[Singleton]
class EncryptedEntityChecker extends EntityChecker
{
    public function __construct(
        private readonly ORMInterface $orm,
        private readonly EncrypterInterface $encrypter,
    ) {
        parent::__construct($this->orm);
    }

    public function exists(
        mixed $value,
        string $role,
        ?string $field = null,
        bool $ignoreCase = false,
        bool $multiple = false,
    ): bool {
        if (is_string($value)) {
            $value = $this->encrypter->encrypt($value);
        }

        return parent::exists($value, $role, $field, $ignoreCase, $multiple);
    }

    public function unique(
        mixed $value,
        string $role,
        string $field,
        array $withFields = [],
        bool $ignoreCase = false,
    ): bool {
        if (is_string($value)) {
            $value = $this->encrypter->encrypt($value);
        }

        return parent::unique($value, $role, $field, $withFields, $ignoreCase);
    }
}
