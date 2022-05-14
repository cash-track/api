<?php

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefaultE5bdbf3992de5b3be4e6dcc59ef01c18 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('wallets')
            ->alterColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => \Cycle\Database\Injection\Fragment::__set_state([
                    'fragment'   => 'CURRENT_TIMESTAMP',
                    'parameters' => [],
                ]),
            ])
            ->update();
        
        $this->table('users')
            ->alterColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => \Cycle\Database\Injection\Fragment::__set_state([
                    'fragment'   => 'CURRENT_TIMESTAMP',
                    'parameters' => [],
                ]),
            ])
            ->update();
        
        $this->table('currency_exchanges')
            ->alterColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => \Cycle\Database\Injection\Fragment::__set_state([
                    'fragment'   => 'CURRENT_TIMESTAMP',
                    'parameters' => [],
                ])
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('currency_exchanges')
            ->alterColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->update();
        
        $this->table('users')
            ->alterColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->update();
        
        $this->table('wallets')
            ->alterColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->update();
    }
}
