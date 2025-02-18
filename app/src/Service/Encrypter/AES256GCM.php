<?php

declare(strict_types=1);

namespace App\Service\Encrypter;

use Spiral\Encrypter\Exception\EncrypterException;

final class AES256GCM implements CipherInterface
{
    const string ALGO = 'aes-256-gcm';
    const int TAG_LENGTH = 16;

    #[\Override]
    public function encrypt(string $value, string $key): string
    {
        $iv = openssl_random_pseudo_bytes((int) openssl_cipher_iv_length(static::ALGO));

        $payload = openssl_encrypt(
            data: $value,
            cipher_algo: static::ALGO,
            passphrase:  $key,
            options: OPENSSL_RAW_DATA,
            iv: $iv,
            tag: $tag,
            tag_length:  static::TAG_LENGTH,
        );

        $payload !== false || throw new EncrypterException('Encryption unsuccessful: ' . (string) openssl_error_string());

        return base64_encode($iv . $payload . $tag);
    }

    #[\Override]
    public function decrypt(string $payload, string $key): string
    {
        $packet = base64_decode($payload);
        $ivLength = (int) openssl_cipher_iv_length(static::ALGO);
        $iv = substr($packet, 0, $ivLength);
        $encrypted = substr($packet, $ivLength, - static::TAG_LENGTH);
        $tag = substr($packet, - static::TAG_LENGTH);

        $value = openssl_decrypt(
            data: $encrypted,
            cipher_algo: static::ALGO,
            passphrase: $key,
            options: OPENSSL_RAW_DATA,
            iv: $iv,
            tag: $tag,
        );

        if ($value === false) {
            throw new EncrypterException('Decryption unsuccessful: ' . (string) openssl_error_string());
        }

        return $value;
    }
}
