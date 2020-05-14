<?php

declare(strict_types=1);

namespace App\Mapper;

class TimestampedUUIDMapper extends TimestampedMapper
{
    use UUIDGenerator;

    /**
     * Generate entity primary key value.
     *
     * @return string
     * @throws \Cycle\ORM\Exception\MapperException
     */
    public function nextPrimaryKey()
    {
        return $this->generateNextUUIDKey();
    }
}
