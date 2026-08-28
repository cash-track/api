<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Tag;
use App\Database\User;
use App\Service\Metrics\AppMetricsInterface;
use Cycle\ORM\EntityManagerInterface;

class TagService
{
    public function __construct(
        private EntityManagerInterface $tr,
        private readonly AppMetricsInterface $metrics,
    ) {
    }

    public function create(Tag $tag, User $user): Tag
    {
        $tag->setUser($user);

        $this->store($tag);

        $this->metrics->incrementTagCreated();

        return $tag;
    }

    public function store(Tag $tag): Tag
    {
        $this->tr->persist($tag);
        $this->tr->run();

        return $tag;
    }

    public function delete(Tag $tag): void
    {
        $this->tr->delete($tag);
        $this->tr->run();
    }
}
