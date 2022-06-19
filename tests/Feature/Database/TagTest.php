<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Tag;
use App\Database\User;
use Tests\TestCase;

class TagTest extends TestCase
{
    public function testGetUserReturnEntity(): void
    {
        $tag = new Tag();

        $this->assertInstanceOf(User::class, $tag->getUser());
    }
}
