<?php

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefault88d72b4a78f3b9a0271e12a1e728d21d extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('users')
            ->addColumn('options', 'json', [
                'nullable' => false,
                'default'  => null
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->dropColumn('options')
            ->update();
    }
}
