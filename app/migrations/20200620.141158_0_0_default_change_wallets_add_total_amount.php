<?php

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefault0118b9624d502b8c449dbcf0f589c092 extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('wallets')
            ->addColumn('total_amount', 'decimal', [
                'nullable'  => false,
                'default'   => 0.0,
                'scale'     => 2,
                'precision' => 13
            ])
            ->update();
    }

    public function down()
    {
        $this->table('wallets')
            ->dropColumn('total_amount')
            ->update();
    }
}
