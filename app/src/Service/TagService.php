<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Tag;
use App\Database\User;
use Cycle\ORM\EntityManagerInterface;

final class TagService
{
    public function __construct(
        private EntityManagerInterface $tr,
    ) {
    }

    public function create(Tag $tag, User $user): Tag
    {
        $tag->setUser($user);

        return $this->store($tag);
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
