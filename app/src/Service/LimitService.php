<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Limit;
use Cycle\ORM\EntityManagerInterface;

class LimitService
{
    public function __construct(private readonly EntityManagerInterface $tr)
    {
    }

    public function store(Limit $limit): Limit
    {
        $this->tr->persist($limit);
        $this->tr->run();

        return $limit;
    }

    public function delete(Limit $limit): void
    {
        $this->tr->delete($limit);
        $this->tr->run();
    }
}
