<?php

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefaultA39392fbabe95480b892abe8ed46feaa extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('tag_charges')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('charge_id', 'uuid', [
                'nullable' => false,
                'default'  => null,
                'size'     => 36
            ])
            ->addColumn('tag_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addIndex(["charge_id", "tag_id"], [
                'name'   => 'tag_charges_index_charge_id_tag_id_6297bdd88517b',
                'unique' => true
            ])
            ->addIndex(["charge_id"], [
                'name'   => 'tag_charges_index_charge_id_6297bdd88518a',
                'unique' => false
            ])
            ->addIndex(["tag_id"], [
                'name'   => 'tag_charges_index_tag_id_6297bdd885195',
                'unique' => false
            ])
            ->addForeignKey(["charge_id"], 'charges', ["id"], [
                'name'   => 'tag_charges_foreign_charge_id_6297bdd885186',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["tag_id"], 'tags', ["id"], [
                'name'   => 'tag_charges_foreign_tag_id_6297bdd885192',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->setPrimaryKeys(["id"])
            ->create();
    }

    public function down(): void
    {
        $this->table('tag_charges')->drop();
    }
}
