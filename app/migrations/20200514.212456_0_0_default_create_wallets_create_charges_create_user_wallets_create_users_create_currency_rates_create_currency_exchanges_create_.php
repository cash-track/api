<?php

namespace Migration;

use Spiral\Migrations\Migration;

class OrmDefault620516534629bbad2ceb26e9fdb6c93a extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('wallets')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('name', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('slug', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('is_active', 'boolean', [
                'nullable' => false,
                'default'  => true
            ])
            ->addColumn('is_archived', 'boolean', [
                'nullable' => false,
                'default'  => false
            ])
            ->addColumn('is_public', 'boolean', [
                'nullable' => false,
                'default'  => false
            ])
            ->addColumn('default_currency_code', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 3
            ])
            ->addColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('updated_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->setPrimaryKeys(["id"])
            ->create();
        
        $this->table('charges')
            ->addColumn('id', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 36
            ])
            ->addColumn('wallet_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('user_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('type', 'enum', [
                'nullable' => false,
                'default'  => '+',
                'values'   => [
                    '+',
                    '-'
                ]
            ])
            ->addColumn('amount', 'decimal', [
                'nullable'  => false,
                'default'   => null,
                'scale'     => 2,
                'precision' => 13
            ])
            ->addColumn('title', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('currency_exchange_id', 'integer', [
                'nullable' => true,
                'default'  => null
            ])
            ->addColumn('description', 'text', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('updated_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->setPrimaryKeys(["id"])
            ->create();
        
        $this->table('user_wallets')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null
            ])
            ->setPrimaryKeys(["id"])
            ->create();
        
        $this->table('users')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('name', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('last_name', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('nick_name', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('email', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('photo_url', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('default_currency_code', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 3
            ])
            ->addColumn('password', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('updated_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->setPrimaryKeys(["id"])
            ->create();
        
        $this->table('currency_rates')
            ->addColumn('code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addColumn('base_currency_code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addColumn('rate', 'decimal', [
                'nullable'  => false,
                'default'   => null,
                'scale'     => 4,
                'precision' => 8
            ])
            ->addColumn('updated_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->setPrimaryKeys(["code"])
            ->create();
        
        $this->table('currency_exchanges')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('src_currency_code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addColumn('src_amount', 'decimal', [
                'nullable'  => false,
                'default'   => null,
                'scale'     => 2,
                'precision' => 13
            ])
            ->addColumn('rate', 'decimal', [
                'nullable'  => false,
                'default'   => null,
                'scale'     => 4,
                'precision' => 8
            ])
            ->addColumn('dst_currency_code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addColumn('dst_amount', 'decimal', [
                'nullable'  => false,
                'default'   => null,
                'scale'     => 2,
                'precision' => 13
            ])
            ->addColumn('created_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('updated_at', 'datetime', [
                'nullable' => false,
                'default'  => null
            ])
            ->setPrimaryKeys(["id"])
            ->create();
        
        $this->table('currencies')
            ->addColumn('code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addColumn('name', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255
            ])
            ->addColumn('char', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 1
            ])
            ->setPrimaryKeys(["code"])
            ->create();
    }

    public function down()
    {
        $this->table('currencies')->drop();
        
        $this->table('currency_exchanges')->drop();
        
        $this->table('currency_rates')->drop();
        
        $this->table('users')->drop();
        
        $this->table('user_wallets')->drop();
        
        $this->table('charges')->drop();
        
        $this->table('wallets')->drop();
    }
}
