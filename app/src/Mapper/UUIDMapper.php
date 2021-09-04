<?php

declare(strict_types=1);

namespace App\Mapper;

use Cycle\ORM\Mapper\Mapper;

class UUIDMapper extends Mapper
{
    use UUIDGenerator;

    /** @var array */
    protected $fields = [];

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
