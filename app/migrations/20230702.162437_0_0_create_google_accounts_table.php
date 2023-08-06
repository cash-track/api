<?php

declare(strict_types=1);

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefault755d95676d683f1d8e11cac1e0c4242d extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('google_accounts')
             ->addColumn('user_id', 'integer', [
                 'nullable' => false,
                 'default' => null,
             ])
             ->addColumn('account_id', 'string', [
                 'nullable' => false,
                 'default' => null,
                 'size' => 255,
             ])
             ->addColumn('picture_url', 'string', [
                 'nullable' => true,
                 'default' => null,
                 'size' => 1024,
             ])
             ->addColumn('data', 'text', [
                 'nullable' => false,
                 'default' => null,
             ])
             ->addColumn('created_at', 'datetime', [
                 'nullable' => false,
                 'default' => null,
             ])
             ->addColumn('updated_at', 'datetime', [
                 'nullable' => false,
                 'default' => null,
             ])
             ->addIndex(['user_id'], [
                 'name' => 'google_accounts_index_user_id_64a1a4c512530',
                 'unique' => false,
             ])
             ->addForeignKey(['user_id'], 'users', ['id'], [
                 'name' => 'google_accounts_foreign_user_id_64a1a4c51253c',
                 'delete' => 'CASCADE',
                 'update' => 'CASCADE',
                 'indexCreate' => true,
             ])
             ->setPrimaryKeys(['user_id'])
             ->create();
    }

    public function down(): void
    {
        $this->table('google_accounts')->drop();
    }
}
