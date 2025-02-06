<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\Tag;
use App\Database\User;
use Tests\Fixtures;

class TagFactory extends AbstractFactory
{
    protected ?User $user = null;

    public function forUser(?User $user = null): TagFactory
    {
        $this->user = $user;

        return $this;
    }

    public function create(?Tag $tag = null): Tag
    {
        $tag = $tag ?? self::make();

        if ($this->user !== null) {
            $tag->setUser($this->user);
        }

        $this->persist($tag);

        return $tag;
    }

    public static function make(): Tag
    {
        $tag = new Tag();

        $tag->name = Fixtures::string();
        $tag->icon = Fixtures::boolean() ? Fixtures::string(2) : null;
        $tag->color = Fixtures::boolean() ? Fixtures::colorHex() : null;
        $tag->createdAt = Fixtures::dateTime();
        $tag->updatedAt = Fixtures::dateTimeAfter($tag->createdAt);

        return $tag;
    }
}
