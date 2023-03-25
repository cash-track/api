<?php

declare(strict_types=1);

namespace App\Security;

use Cycle\ORM\ORMInterface;
use Spiral\Validator\AbstractChecker;

class UniqueChecker extends AbstractChecker
{
    public const MESSAGES = [
        'verify' => 'Value should be unique.'
    ];

    public function __construct(private ORMInterface $orm)
    {
    }

    public function verify(mixed $value, string $role, string $field, array $withFields = [], array $exceptFields = []): bool
    {
        $values = $this->withValues($withFields);
        $values[$field] = $value;

        $exceptValues = $this->withValues($exceptFields);

        if (empty($role)) {
            return false;
        }

        /** @var \Cycle\ORM\Select\Repository $repository */
        $repository = $this->orm->getRepository($role);

        $select = $repository->select();

        foreach ($values as $field => $value) {
            $select->where($field, $value);
        }

        foreach ($exceptValues as $field => $value) {
            $select->where($field, '!=', $value);
        }

        return $select->fetchOne() === null;
    }

    private function withValues(array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            if ($this->getValidator()->hasValue($field)) {
                $values[$field] = $this->getValidator()->getValue($field);
            }
        }

        return $values;
    }
}
