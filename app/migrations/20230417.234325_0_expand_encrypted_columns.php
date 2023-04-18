<?php

declare(strict_types=1);

namespace App;

use Cycle\Migrations\Migration;

class ExpandEncryptedColumnsMigration extends Migration
{
    public function up(): void
    {
        $this->table('users')
             ->alterColumn('name', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 1536,
             ])
             ->alterColumn('last_name', 'string', [
                 'nullable' => true,
                 'default'  => null,
                 'size'     => 1536
             ])
             ->alterColumn('nick_name', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 767
             ])
             ->alterColumn('email', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 676
             ])
             ->update();

        $this->table('wallets')
             ->alterColumn('name', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 1536
             ])
             ->alterColumn('slug', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 1536
             ])
            ->update();
    }

    public function down(): void
    {
        $this->table('users')
             ->alterColumn('name', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 255,
             ])
             ->alterColumn('last_name', 'string', [
                 'nullable' => true,
                 'default'  => null,
                 'size'     => 255
             ])
             ->alterColumn('nick_name', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 255
             ])
             ->alterColumn('email', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 255
             ])
            ->update();

        $this->table('wallets')
             ->alterColumn('name', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 255
             ])
             ->alterColumn('slug', 'string', [
                 'nullable' => false,
                 'default'  => null,
                 'size'     => 255
             ])
            ->update();
    }
}
