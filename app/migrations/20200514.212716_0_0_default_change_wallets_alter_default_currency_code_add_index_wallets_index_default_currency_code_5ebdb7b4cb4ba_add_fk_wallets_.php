<?php

namespace Migration;

use Spiral\Migrations\Migration;

class OrmDefault0ec38a9cecfb9dbcd9e7c86abbc92438 extends Migration
{
    protected const DATABASE = 'default';

    public function up()
    {
        $this->table('currency_rates')
            ->addIndex(["base_currency_code"], [
                'name'   => 'currency_rates_index_base_currency_code_5ebdb7b4cc0bb',
                'unique' => false
            ])
            ->addIndex(["code"], [
                'name'   => 'currency_rates_index_code_5ebdb7b4cc14c',
                'unique' => false
            ])
            ->addForeignKey(["base_currency_code"], 'currencies', ["code"], [
                'name'   => 'currency_rates_foreign_base_currency_code_5ebdb7b4cc0c4',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["code"], 'currencies', ["code"], [
                'name'   => 'currency_rates_foreign_code_5ebdb7b4cc155',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->update();
        
        $this->table('currencies')
            ->addIndex(["code"], [
                'name'   => 'currencies_index_code_5ebdb7b4cc090',
                'unique' => false
            ])
            ->addForeignKey(["code"], 'currency_rates', ["code"], [
                'name'   => 'currencies_foreign_code_5ebdb7b4cc09b',
                'delete' => 'NO ACTION',
                'update' => 'NO ACTION'
            ])
            ->update();
        
        $this->table('wallets')
            ->alterColumn('default_currency_code', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 3
            ])
            ->addIndex(["default_currency_code"], [
                'name'   => 'wallets_index_default_currency_code_5ebdb7b4cb4ba',
                'unique' => false
            ])
            ->addForeignKey(["default_currency_code"], 'currencies', ["code"], [
                'name'   => 'wallets_foreign_default_currency_code_5ebdb7b4cb6e6',
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
                'name'   => 'users_index_default_currency_code_5ebdb7b4cbfdd',
                'unique' => false
            ])
            ->addForeignKey(["default_currency_code"], 'currencies', ["code"], [
                'name'   => 'users_foreign_default_currency_code_5ebdb7b4cbfea',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->update();
        
        $this->table('currency_exchanges')
            ->addIndex(["src_currency_code"], [
                'name'   => 'currency_exchanges_index_src_currency_code_5ebdb7b4cc0ec',
                'unique' => false
            ])
            ->addIndex(["dst_currency_code"], [
                'name'   => 'currency_exchanges_index_dst_currency_code_5ebdb7b4cc11b',
                'unique' => false
            ])
            ->addForeignKey(["src_currency_code"], 'currencies', ["code"], [
                'name'   => 'currency_exchanges_foreign_src_currency_code_5ebdb7b4cc0f4',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["dst_currency_code"], 'currencies', ["code"], [
                'name'   => 'currency_exchanges_foreign_dst_currency_code_5ebdb7b4cc124',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->update();
        
        $this->table('charges')
            ->addIndex(["wallet_id"], [
                'name'   => 'charges_index_wallet_id_5ebdb7b4cbe56',
                'unique' => false
            ])
            ->addIndex(["user_id"], [
                'name'   => 'charges_index_user_id_5ebdb7b4cbf70',
                'unique' => false
            ])
            ->addIndex(["currency_exchange_id"], [
                'name'   => 'charges_index_currency_exchange_id_5ebdb7b4cbfa9',
                'unique' => false
            ])
            ->addForeignKey(["wallet_id"], 'wallets', ["id"], [
                'name'   => 'charges_foreign_wallet_id_5ebdb7b4cbe6a',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["user_id"], 'users', ["id"], [
                'name'   => 'charges_foreign_user_id_5ebdb7b4cbf7a',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["currency_exchange_id"], 'currency_exchanges', ["id"], [
                'name'   => 'charges_foreign_currency_exchange_id_5ebdb7b4cbfb6',
                'delete' => 'SET NULL',
                'update' => 'SET NULL'
            ])
            ->update();
        
        $this->table('user_wallets')
            ->addColumn('wallet_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addColumn('user_id', 'integer', [
                'nullable' => false,
                'default'  => null
            ])
            ->addIndex(["wallet_id", "user_id"], [
                'name'   => 'user_wallets_index_wallet_id_user_id_5ebdb7b4cbec4',
                'unique' => true
            ])
            ->addIndex(["wallet_id"], [
                'name'   => 'user_wallets_index_wallet_id_5ebdb7b4cbed8',
                'unique' => false
            ])
            ->addIndex(["user_id"], [
                'name'   => 'user_wallets_index_user_id_5ebdb7b4cbefa',
                'unique' => false
            ])
            ->addIndex(["user_id", "wallet_id"], [
                'name'   => 'user_wallets_index_user_id_wallet_id_5ebdb7b4cc046',
                'unique' => true
            ])
            ->addForeignKey(["wallet_id"], 'wallets', ["id"], [
                'name'   => 'user_wallets_foreign_wallet_id_5ebdb7b4cbed1',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addForeignKey(["user_id"], 'users', ["id"], [
                'name'   => 'user_wallets_foreign_user_id_5ebdb7b4cbef3',
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->update();
    }

    public function down()
    {
        $this->table('user_wallets')
            ->dropForeignKey(["wallet_id"])
            ->dropForeignKey(["user_id"])
            ->dropIndex(["wallet_id", "user_id"])
            ->dropIndex(["wallet_id"])
            ->dropIndex(["user_id"])
            ->dropIndex(["user_id", "wallet_id"])
            ->dropColumn('wallet_id')
            ->dropColumn('user_id')
            ->update();
        
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
            ->dropForeignKey(["code"])
            ->dropIndex(["code"])
            ->update();
        
        $this->table('currency_rates')
            ->dropForeignKey(["base_currency_code"])
            ->dropForeignKey(["code"])
            ->dropIndex(["base_currency_code"])
            ->dropIndex(["code"])
            ->update();
    }
}
