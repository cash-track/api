<?php

declare(strict_types=1);

namespace App\Service\Encrypter;

use Spiral\Encrypter\Exception\EncrypterException;

class AES256ECB implements CipherInterface
{
    const string ALGO = 'aes-256-ecb';

    public function encrypt(string $value, string $key): string
    {
        $payload = openssl_encrypt($value, static::ALGO, $key);
        $payload !== false || throw new EncrypterException('Encryption unsuccessful: ' . (string) openssl_error_string());

        return $payload;
    }

    public function decrypt(string $payload, string $key): string
    {
        $value = openssl_decrypt($payload, static::ALGO, $key);

        if ($value === false) {
            throw new EncrypterException('Decryption unsuccessful: ' . (string) openssl_error_string());
        }

        return $value;
    }
}
