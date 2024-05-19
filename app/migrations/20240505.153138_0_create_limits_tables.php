<?php

declare(strict_types=1);

namespace App;

use Cycle\Migrations\Migration;

class CreateLimitsTablesMigration extends Migration
{
    public function up(): void
    {
        $this->table('limits')
             ->addColumn('id', 'primary', [
                 'nullable' => false,
             ])
             ->addColumn('wallet_id', 'integer', [
                 'nullable' => false,
                 'default'  => null
             ])
             ->addColumn('type', 'enum', [
                 'nullable' => false,
                 'default'  => '+',
                 'values'   => ['+', '-']
             ])
             ->addColumn('amount', 'decimal', [
                 'nullable'  => false,
                 'default'   => null,
                 'scale'     => 2,
                 'precision' => 13
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
                 'default'  => null
             ])
             ->addIndex(['wallet_id'], [
                 'name'   => 'limits_index_wallet_id_5ec06ad52938f',
                 'unique' => false
             ])
            ->addForeignKey(['wallet_id'], 'wallets', ['id'], [
                'name'   => 'limits_foreign_wallet_id_5ec06ad52939a',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
             ->setPrimaryKeys(['id'])
             ->create();

        $this->table('tag_limits')
             ->addColumn('id', 'primary', [
                 'nullable' => false,
                 'default'  => null
             ])
             ->addColumn('limit_id', 'integer', [
                 'nullable' => false,
                 'default'  => null,
             ])
             ->addColumn('tag_id', 'integer', [
                 'nullable' => false,
                 'default'  => null
             ])
             ->addIndex(['limit_id', 'tag_id'], [
                 'name'   => 'tag_limits_index_limit_id_tag_id_6297bdd88517b',
                 'unique' => true
             ])
             ->addIndex(['limit_id'], [
                 'name'   => 'tag_limits_index_limit_id_6297bdd88518a',
                 'unique' => false
             ])
             ->addIndex(['tag_id'], [
                 'name'   => 'tag_limits_index_tag_id_6297bdd885195',
                 'unique' => false
             ])
             ->addForeignKey(['limit_id'], 'limits', ['id'], [
                 'name'   => 'tag_limits_foreign_limit_id_6297bdd885186',
                 'delete' => 'CASCADE',
                 'update' => 'CASCADE'
             ])
             ->addForeignKey(['tag_id'], 'tags', ['id'], [
                 'name'   => 'tag_limits_foreign_tag_id_6297bdd885192',
                 'delete' => 'CASCADE',
                 'update' => 'CASCADE'
             ])
             ->setPrimaryKeys(['id'])
             ->create();
    }

    public function down(): void
    {
        $this->table('tag_limits')->drop();
        $this->table('limits')->drop();
    }
}
