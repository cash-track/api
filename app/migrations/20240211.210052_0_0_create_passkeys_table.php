<?php

declare(strict_types=1);

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefault08622d96d0395b7fef476fde4deb490d extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('passkeys')
             ->addColumn('id', 'primary', [
                 'nullable' => false,
             ])
             ->addColumn('user_id', 'integer', [
                 'nullable' => false,
             ])
             ->addColumn('name', 'string', [
                 'nullable' => false,
                 'size' => 1536,
             ])
             ->addColumn('key_id', 'string', [
                 'nullable' => false,
                 'size' => 6144,
             ])
             ->addColumn('data', 'text', [
                 'nullable' => false,
             ])
             ->addColumn('created_at', 'datetime', [
                 'nullable' => false,
                 'default' => 'CURRENT_TIMESTAMP',
             ])
             ->addColumn('used_at', 'datetime', [
                 'nullable' => true,
                 'default' => null,
             ])
             ->addForeignKey(['user_id'], 'users', ['id'], [
                 'name' => 'passkeys_foreign_user_id_64a1a5c61253c',
                 'delete' => 'CASCADE',
                 'update' => 'CASCADE',
                 'indexCreate' => true,
             ])
             ->setPrimaryKeys(['id'])
             ->create();
    }

    public function down(): void
    {
        $this->table('passkeys')->drop();
    }
}
