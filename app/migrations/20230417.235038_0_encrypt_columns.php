<?php

declare(strict_types=1);

namespace App;

use App\Database\Encrypter\EncrypterInterface;
use Cycle\Migrations\Migration;

class EncryptColumnsMigration extends Migration
{
    public function __construct(
        private readonly EncrypterInterface $encrypter
    ) {}

    public function up(): void
    {
        $items = $this->database()->select(['id', 'name', 'last_name', 'nick_name', 'email'])->from('users')->fetchAll();

        foreach ($items as $user) {
            $this->database()->update('users', [
                'name' => $this->encrypter->encrypt($user['name']),
                'last_name' => $this->encrypter->encrypt($user['last_name']),
                'nick_name' => $this->encrypter->encrypt($user['nick_name']),
                'email' => $this->encrypter->encrypt($user['email']),
            ], [
                'id' => $user['id']
            ])->run();
        }

        $items = $this->database()->select(['id', 'name', 'slug'])->from('wallets')->fetchAll();

        foreach ($items as $wallet) {
            $this->database()->update('wallets', [
                'name' => $this->encrypter->encrypt($wallet['name']),
                'slug' => $this->encrypter->encrypt($wallet['slug']),
            ], [
                'id' => $wallet['id']
            ])->run();
        }
    }

    public function down(): void
    {
        //
    }
}
