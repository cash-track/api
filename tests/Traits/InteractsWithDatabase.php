<?php

declare(strict_types=1);

namespace Tests\Traits;

trait InteractsWithDatabase
{
    public function assertDatabaseHas(string $table, array $where)
    {
        $data = $this->db->select()->from($table)->where($where)->fetchAll();

        $this->assertNotEmpty($data);
    }

    public function assertDatabaseMissing(string $table, array $where)
    {
        $data = $this->db->select()->from($table)->where($where)->fetchAll();

        $this->assertEmpty($data);
    }

    public function assertDatabaseCount(int $expected, string $table, array $where)
    {
        $data = $this->db->select()->from($table)->where($where)->fetchAll();

        $this->assertCount($expected, $data);
    }
}
