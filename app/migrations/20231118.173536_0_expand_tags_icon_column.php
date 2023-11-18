<?php

declare(strict_types=1);

namespace App;

use Cycle\Migrations\Migration;

class ExpandTagsIconColumnMigration extends Migration
{
    public function up(): void
    {
        $this->table('tags')
             ->alterColumn('icon', 'string', [
                 'nullable' => true,
                 'default'  => null,
                 'size'     => 4,
             ])
            ->update();
    }

    public function down(): void
    {
        $this->table('tags')
             ->alterColumn('icon', 'string', [
                 'nullable' => true,
                 'default'  => null,
                 'size'     => 2,
             ])
            ->update();
    }
}
