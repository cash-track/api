<?php

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefault15dd708f085fdb470a11415db69ec4fa extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('tags')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('name', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('user_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('icon', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 2
            ])
            ->addColumn('color', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => \Cycle\Database\Injection\Fragment::__set_state([
                    'fragment'   => 'CURRENT_TIMESTAMP',
                    'parameters' => [],
                ]),
            ])
            ->addColumn('updated_at', 'datetime', [
                'nullable' => false,
                'default'  => \Cycle\Database\Injection\Fragment::__set_state([
                    'fragment'   => 'CURRENT_TIMESTAMP',
                    'parameters' => [],
                ]),
            ])
            ->addIndex(["user_id"], [
                'name'   => 'tags_index_user_id_6293720993318',
                'unique' => false
            ])
            ->addIndex(["name", "user_id"], [
                'name'   => 'tags_index_name_user_id_62937820f0e7d',
                'unique' => true
            ])
            ->addForeignKey(["user_id"], 'users', ["id"], [
                'name'   => 'tags_foreign_user_id_6293720993321',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->setPrimaryKeys(["id"])
            ->create();
    }

    public function down(): void
    {
        $this->table('tags')->drop();
    }
}
