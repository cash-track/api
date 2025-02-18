<?php

declare(strict_types=1);

namespace App\Database;

use App\Service\Mailer\PayloadSerializer;
use Cycle\ORM\ORMInterface;

/**
 * @template TEntity of object
 */
final class EntityHeader
{
    use PayloadSerializer;

    /**
     * @param class-string $role
     * @param array $params
     */
    public function __construct(
        public string $role,
        public array $params
    ) {
    }

    /**
     * @param \Cycle\ORM\ORMInterface $orm
     * @return TEntity|null
     */
    public function hydrate(ORMInterface $orm): ?object
    {
        /** @var \Cycle\ORM\Select\Repository<TEntity> $repository */
        $repository = $orm->getRepository($this->role);

        $query = $repository->select();

        foreach ($this->params as $field => $value) {
            $query->where($field, $value);
        }

        return $query->fetchOne();
    }
}
