<?php

declare(strict_types=1);

namespace Tests\Traits;

use Cycle\Database\DatabaseInterface;

trait InteractsWithDatabase
{
    public function getDatabase(): DatabaseInterface
    {
        return $this->getContainer()->get(DatabaseInterface::class);
    }

    public function assertDatabaseHas(string $table, array $where)
    {
        $data = $this->getDatabase()->select()->from($table)->where($where)->fetchAll();

        $this->assertNotEmpty($data);
    }

    public function assertDatabaseMissing(string $table, array $where)
    {
        $data = $this->getDatabase()->select()->from($table)->where($where)->fetchAll();

        $this->assertEmpty($data);
    }

    public function assertDatabaseCount(int $expected, string $table, array $where)
    {
        $data = $this->getDatabase()->select()->from($table)->where($where)->fetchAll();

        $this->assertCount($expected, $data);
    }
}
