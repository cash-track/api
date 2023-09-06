<?php

declare(strict_types=1);

namespace App;

use Cycle\Migrations\Migration;

class AddActiveAtColumnToUsersTableMigration extends Migration
{
    public function up(): void
    {
        $this->table('users')
             ->addColumn('active_at', 'datetime', [
                 'nullable' => true,
                 'default'  => null,
             ])
             ->update();
    }

    public function down(): void
    {
        $this->table('users')
             ->dropColumn('active_at')
             ->update();
    }
}
