<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Database\Encrypter\EncrypterInterface;
use Cycle\Database\DatabaseInterface;

trait InteractsWithDatabase
{
    protected function encryptWhere(array $where): array
    {
        $encrypter = $this->getContainer()->get(EncrypterInterface::class);

        foreach ($where as $key => $value) {
            $where[$key] = $encrypter->encrypt($value);
        }

        return $where;
    }

    public function getDatabase(): DatabaseInterface
    {
        return $this->getContainer()->get(DatabaseInterface::class);
    }

    public function assertDatabaseHas(string $table, array $where, array $whereEncrypted = [])
    {
        $this->assertNotEmpty($this->queryDatabase($table, $where, $whereEncrypted));
    }

    public function assertDatabaseMissing(string $table, array $where, array $whereEncrypted = [])
    {
        $this->assertEmpty($this->queryDatabase($table, $where, $whereEncrypted));
    }

    public function assertDatabaseCount(int $expected, string $table, array $where, array $whereEncrypted = [])
    {
        $this->assertCount($expected, $this->queryDatabase($table, $where, $whereEncrypted));
    }

    public function queryDatabase(string $table, array $where = [], array $whereEncrypted = []): array
    {
        return $this->getDatabase()
                     ->select()
                     ->from($table)
                     ->where($where)
                     ->where($this->encryptWhere($whereEncrypted))
                     ->fetchAll();
    }
}
