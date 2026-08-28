<?php

declare(strict_types=1);

namespace App;

use Cycle\Migrations\Migration;

class AddIndexToUsersActiveAtMigration extends Migration
{
    public function up(): void
    {
        $this->table('users')
             ->addIndex(['active_at'], [
                 'name'   => 'users_index_active_at',
                 'unique' => false,
             ])
             ->update();
    }

    public function down(): void
    {
        $this->table('users')
             ->dropIndex(['active_at'])
             ->update();
    }
}
