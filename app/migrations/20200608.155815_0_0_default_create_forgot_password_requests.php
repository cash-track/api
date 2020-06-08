<?php

namespace Migration;

use Spiral\Migrations\Migration;

class OrmDefault5060ebced5f00308446fda826e09c5bf extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('forgot_password_requests')
            ->addColumn('email', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->addIndex(["code"], [
                'name'   => 'forgot_password_requests_index_code_5ede6017cad21',
                'unique' => false
            ])
            ->addIndex(["email"], [
                'name'   => 'forgot_password_requests_index_email_5ede6017cad2f',
                'unique' => true
            ])
            ->setPrimaryKeys(["email"])
            ->create();
    }

    public function down()
    {
        $this->table('forgot_password_requests')->drop();
    }
}
