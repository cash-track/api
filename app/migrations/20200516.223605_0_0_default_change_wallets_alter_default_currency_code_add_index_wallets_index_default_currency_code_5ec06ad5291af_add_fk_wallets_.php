<?php

declare(strict_types=1);

namespace Migration;

use Cycle\Migrations\Migration;

class OrmDefault7af405532addf215012ad5dac76567ea extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('currencies')
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
            ->update();
        
        $this->table('wallets')
            ->alterColumn('default_currency_code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addIndex(["default_currency_code"], [
                'name'   => 'wallets_index_default_currency_code_5ec06ad5291af',
                'unique' => false
            ])
            ->addForeignKey(["default_currency_code"], 'currencies', ["code"], [
                'name'   => 'wallets_foreign_default_currency_code_5ec06ad52921f',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->update();
        
        $this->table('users')
            ->alterColumn('default_currency_code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addIndex(["default_currency_code"], [
                'name'   => 'users_index_default_currency_code_5ec06ad529498',
                'unique' => false
            ])
            ->addForeignKey(["default_currency_code"], 'currencies', ["code"], [
                'name'   => 'users_foreign_default_currency_code_5ec06ad52949f',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->update();
        
        $this->table('currency_exchanges')
            ->addIndex(["src_currency_code"], [
                'name'   => 'currency_exchanges_index_src_currency_code_5ec06ad52950a',
                'unique' => false
            ])
            ->addIndex(["dst_currency_code"], [
                'name'   => 'currency_exchanges_index_dst_currency_code_5ec06ad52952f',
                'unique' => false
            ])
            ->addForeignKey(["src_currency_code"], 'currencies', ["code"], [
                'name'   => 'currency_exchanges_foreign_src_currency_code_5ec06ad529511',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["dst_currency_code"], 'currencies', ["code"], [
                'name'   => 'currency_exchanges_foreign_dst_currency_code_5ec06ad529536',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->update();
        
        $this->table('charges')
            ->addIndex(["wallet_id"], [
                'name'   => 'charges_index_wallet_id_5ec06ad52938f',
                'unique' => false
            ])
            ->addIndex(["user_id"], [
                'name'   => 'charges_index_user_id_5ec06ad529453',
                'unique' => false
            ])
            ->addIndex(["currency_exchange_id"], [
                'name'   => 'charges_index_currency_exchange_id_5ec06ad529479',
                'unique' => false
            ])
            ->addForeignKey(["wallet_id"], 'wallets', ["id"], [
                'name'   => 'charges_foreign_wallet_id_5ec06ad52939a',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["user_id"], 'users', ["id"], [
                'name'   => 'charges_foreign_user_id_5ec06ad52945a',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["currency_exchange_id"], 'currency_exchanges', ["id"], [
                'name'   => 'charges_foreign_currency_exchange_id_5ec06ad529480',
                'delete' => 'SET NULL',
                'update' => 'SET NULL'
            ])
            ->update();
        
        $this->table('user_wallets')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('wallet_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('user_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addIndex(["wallet_id", "user_id"], [
                'name'   => 'user_wallets_index_wallet_id_user_id_5ec06ad5293dc',
                'unique' => true
            ])
            ->addIndex(["wallet_id"], [
                'name'   => 'user_wallets_index_wallet_id_5ec06ad5293ea',
                'unique' => false
            ])
            ->addIndex(["user_id"], [
                'name'   => 'user_wallets_index_user_id_5ec06ad529403',
                'unique' => false
            ])
            ->addIndex(["user_id", "wallet_id"], [
                'name'   => 'user_wallets_index_user_id_wallet_id_5ec06ad5294db',
                'unique' => true
            ])
            ->addForeignKey(["wallet_id"], 'wallets', ["id"], [
                'name'   => 'user_wallets_foreign_wallet_id_5ec06ad5293e6',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["user_id"], 'users', ["id"], [
                'name'   => 'user_wallets_foreign_user_id_5ec06ad5293fe',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->setPrimaryKeys(["id"])
            ->create();
    }

    public function down()
    {
        $this->table('user_wallets')->drop();
        
        $this->table('charges')
            ->dropForeignKey(["wallet_id"])
            ->dropForeignKey(["user_id"])
            ->dropForeignKey(["currency_exchange_id"])
            ->dropIndex(["wallet_id"])
            ->dropIndex(["user_id"])
            ->dropIndex(["currency_exchange_id"])
            ->update();
        
        $this->table('currency_exchanges')
            ->dropForeignKey(["src_currency_code"])
            ->dropForeignKey(["dst_currency_code"])
            ->dropIndex(["src_currency_code"])
            ->dropIndex(["dst_currency_code"])
            ->update();
        
        $this->table('users')
            ->dropForeignKey(["default_currency_code"])
            ->dropIndex(["default_currency_code"])
            ->alterColumn('default_currency_code', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 3
            ])
            ->update();
        
        $this->table('wallets')
            ->dropForeignKey(["default_currency_code"])
            ->dropIndex(["default_currency_code"])
            ->alterColumn('default_currency_code', 'string', [
                'nullable' => true,
                'default'  => null,
                'size'     => 3
            ])
            ->update();
        
        $this->table('currencies')
            ->dropColumn('rate')
            ->dropColumn('updated_at')
            ->update();
    }
}
