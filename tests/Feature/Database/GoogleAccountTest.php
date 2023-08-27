<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\GoogleAccount;
use Tests\Fixtures;
use Tests\TestCase;

class GoogleAccountTest extends TestCase
{
    public function testGetSetData(): void
    {
        $entity = new GoogleAccount();

        $this->assertEquals([], $entity->getData());

        $entity->data = '{one:two}';
        $this->assertEquals([], $entity->getData());

        $data = ['googleId' => Fixtures::integer(), 'photoUrl' => Fixtures::url()];
        $entity->setData($data);
        $this->assertEquals($data, $entity->getData());

        $entity->setData(['key' => fopen('/dev/null', 'r')]);
        $this->assertEquals([], $entity->getData());
    }
}
