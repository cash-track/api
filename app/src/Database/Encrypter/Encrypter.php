<?php

declare(strict_types=1);

namespace App\Database\Encrypter;

use App\Config\AppConfig;
use Spiral\Encrypter\Exception\EncrypterException;

class Encrypter implements EncrypterInterface
{
    const CIPHER = 'AES-256-ECB';

    private string $key;

    public function __construct(private readonly AppConfig $config)
    {
        $this->key = $this->config->getDbEncrypterKey();
    }

    public function encrypt(string $value): string
    {
        if (! $this->isEnabled()) {
            return $value;
        }

        $payload = openssl_encrypt($value, static::CIPHER, $this->key);
        $payload !== false || throw new EncrypterException('Encryption unsuccessful: ' . openssl_error_string());

        return $payload;
    }

    public function decrypt(string $payload): string
    {
        if (! $this->isEnabled()) {
            return $payload;
        }

        $value = openssl_decrypt($payload, static::CIPHER, $this->key);

        if ($value === false) {
            throw new EncrypterException('Decryption unsuccessful: ' . openssl_error_string());
        }

        return $value;
    }

    private function isEnabled(): bool
    {
        return !empty($this->key);
    }
}
