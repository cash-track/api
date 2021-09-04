<?php

declare(strict_types=1);

namespace App\Mapper;

use Cycle\ORM\Exception\MapperException;
use Ramsey\Uuid\Uuid;

trait UUIDGenerator
{
    /**
     * @return string
     * @throws \Cycle\ORM\Exception\MapperException
     */
    public function generateNextUUIDKey(): string
    {
        try {
            return Uuid::uuid4()->toString();
        } catch (\Exception $e) {
            throw new MapperException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
