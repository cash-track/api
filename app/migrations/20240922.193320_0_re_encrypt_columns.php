<?php

declare(strict_types=1);

namespace App;

use App\Service\Encrypter\Cipher;
use App\Service\Encrypter\EncrypterInterface;
use Cycle\Migrations\Migration;

class ReEncryptColumnsMigration extends Migration
{
    public function __construct(
        private readonly EncrypterInterface $encrypter
    ) {}

    public function up(): void
    {
        $items = $this->database()->select(['user_id', 'account_id', 'picture_url', 'data'])->from('google_accounts')->fetchAll();

        foreach ($items as $googleAccount) {
            $this->database()->update('google_accounts', [
                'account_id' => $this->convert($googleAccount['account_id']),
                'picture_url' => $this->convert($googleAccount['picture_url']),
                'data' => $this->convert($googleAccount['data']),
            ], [
                'user_id' => $googleAccount['user_id']
            ])->run();
        }

        $items = $this->database()->select(['id', 'name', 'data'])->from('passkeys')->fetchAll();

        foreach ($items as $passkey) {
            $this->database()->update('passkeys', [
                'name' => $this->convert($passkey['name']),
                'data' => $this->convert($passkey['data']),
            ], [
                'id' => $passkey['id']
            ])->run();
        }

        $items = $this->database()->select(['id', 'name', 'last_name'])->from('users')->fetchAll();

        foreach ($items as $user) {
            $this->database()->update('users', [
                'name' => $this->convert($user['name']),
                'last_name' => $this->convert($user['last_name']),
            ], [
                'id' => $user['id']
            ])->run();
        }

        $items = $this->database()->select(['id', 'name'])->from('wallets')->fetchAll();

        foreach ($items as $wallet) {
            $this->database()->update('wallets', [
                'name' => $this->convert($wallet['name']),
            ], [
                'id' => $wallet['id']
            ])->run();
        }
    }

    public function down(): void
    {
        //
    }

    private function convert(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        return $this->encrypter->encrypt(
            $this->encrypter->decrypt($value, Cipher::AES256ECB),
            Cipher::AES256GCM,
        );
    }
}
