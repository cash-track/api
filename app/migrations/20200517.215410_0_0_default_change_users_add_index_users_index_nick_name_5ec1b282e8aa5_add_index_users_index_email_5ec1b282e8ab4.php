<?php

namespace Migration;

use Spiral\Migrations\Migration;

class OrmDefault00f2b22c4b9ee1adca37124fa74c6cf1 extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('users')
            ->addIndex(["nick_name"], [
                'name'   => 'users_index_nick_name_5ec1b282e8aa5',
                'unique' => true
            ])
            ->addIndex(["email"], [
                'name'   => 'users_index_email_5ec1b282e8ab4',
                'unique' => true
            ])
            ->update();
    }

    public function down()
    {
        $this->table('users')
            ->dropIndex(["nick_name"])
            ->dropIndex(["email"])
            ->update();
    }
}
