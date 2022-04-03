<?php

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefault073c14645116a9c5a02eb5d8108ef240 extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('users')
            ->addColumn('photo', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 255,
                'after' => 'email',
            ])
            ->dropColumn('photo_url')
            ->update();
    }

    public function down()
    {
        $this->table('users')
            ->addColumn('photo_url', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 255,
                'after' => 'email',
            ])
            ->dropColumn('photo')
            ->update();
    }
}
