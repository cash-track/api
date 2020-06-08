<?php

namespace Migration;

use Spiral\Migrations\Migration;

class OrmDefaultD22f5560ba269799727089c99eb0c5fa extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('users')
            ->addColumn('is_email_confirmed', 'boolean', [
                'nullable' => false,
                'default'  => false
            ])
            ->update();
        
        $this->table('email_confirmations')
            ->addColumn('email', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('token', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->addIndex(["token"], [
                'name'   => 'email_confirmations_index_token_5edd190c1c4a9',
                'unique' => false
            ])
            ->addIndex(["email"], [
                'name'   => 'email_confirmations_index_email_5edd190c1c4c1',
                'unique' => true
            ])
            ->setPrimaryKeys(["email"])
            ->create();
    }

    public function down()
    {
        $this->table('email_confirmations')->drop();
        
        $this->table('users')
            ->dropColumn('is_email_confirmed')
            ->update();
    }
}
