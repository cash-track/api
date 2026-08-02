<?php

declare(strict_types=1);

namespace App;

use Cycle\Migrations\Migration;

class CreateLimitTagGroupsTablesMigration extends Migration
{
    public function up(): void
    {
        $this->table('limit_tag_groups')
             ->addColumn('id', 'primary', [
                 'nullable' => false,
                 'default'  => null,
             ])
             ->addColumn('limit_id', 'integer', [
                 'nullable' => false,
                 'default'  => null,
             ])
             ->addColumn('connection', 'enum', [
                 'nullable' => false,
                 'default'  => 'or',
                 'values'   => ['and', 'or'],
             ])
             ->addIndex(['limit_id'], [
                 'name'   => 'limit_tag_groups_index_limit_id',
                 'unique' => false,
             ])
             ->addForeignKey(['limit_id'], 'limits', ['id'], [
                 'name'   => 'limit_tag_groups_foreign_limit_id',
                 'delete' => 'CASCADE',
                 'update' => 'CASCADE',
             ])
             ->setPrimaryKeys(['id'])
             ->create();

        $this->table('tag_limit_tag_groups')
             ->addColumn('id', 'primary', [
                 'nullable' => false,
                 'default'  => null,
             ])
             ->addColumn('limit_tag_group_id', 'integer', [
                 'nullable' => false,
                 'default'  => null,
             ])
             ->addColumn('tag_id', 'integer', [
                 'nullable' => false,
                 'default'  => null,
             ])
             ->addIndex(['limit_tag_group_id', 'tag_id'], [
                 'name'   => 'tag_limit_tag_groups_index_limit_tag_group_id_tag_id',
                 'unique' => true,
             ])
             ->addIndex(['limit_tag_group_id'], [
                 'name'   => 'tag_limit_tag_groups_index_limit_tag_group_id',
                 'unique' => false,
             ])
             ->addIndex(['tag_id'], [
                 'name'   => 'tag_limit_tag_groups_index_tag_id',
                 'unique' => false,
             ])
             ->addForeignKey(['limit_tag_group_id'], 'limit_tag_groups', ['id'], [
                 'name'   => 'tag_limit_tag_groups_foreign_limit_tag_group_id',
                 'delete' => 'CASCADE',
                 'update' => 'CASCADE',
             ])
             ->addForeignKey(['tag_id'], 'tags', ['id'], [
                 'name'   => 'tag_limit_tag_groups_foreign_tag_id',
                 'delete' => 'CASCADE',
                 'update' => 'CASCADE',
             ])
             ->setPrimaryKeys(['id'])
             ->create();

        // Backfill: every existing limit gets one AND-connected group carrying over its legacy flat tags,
        // so LimitService::calculate() (which now reads tagGroups) keeps computing the same amounts.
        $this->database()->execute("
            INSERT INTO limit_tag_groups (limit_id, connection)
            SELECT id, 'and' FROM limits
        ");

        $this->database()->execute('
            INSERT INTO tag_limit_tag_groups (limit_tag_group_id, tag_id)
            SELECT limit_tag_groups.id, tag_limits.tag_id
            FROM tag_limits
            INNER JOIN limit_tag_groups ON limit_tag_groups.limit_id = tag_limits.limit_id
        ');
    }

    public function down(): void
    {
        $this->table('tag_limit_tag_groups')->drop();
        $this->table('limit_tag_groups')->drop();
    }
}
