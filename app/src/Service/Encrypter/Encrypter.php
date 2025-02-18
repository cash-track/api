<?php

declare(strict_types=1);

namespace App\Service\Encrypter;

use App\Config\AppConfig;

class Encrypter implements EncrypterInterface
{
    private readonly string $key;

    public function __construct(private readonly AppConfig $appConfig)
    {
        $this->key = $this->appConfig->getDbEncrypterKey();
    }

    #[\Override]
    public function encrypt(string $value, ?Cipher $cipher = null): string
    {
        if (! $this->isEnabled()) {
            return $value;
        }

        return $this->getCipherInstance($cipher)->encrypt($value, $this->key);
    }

    #[\Override]
    public function decrypt(string $payload, ?Cipher $cipher = null): string
    {
        if (! $this->isEnabled()) {
            return $payload;
        }

        return $this->getCipherInstance($cipher)->decrypt($payload, $this->key);
    }

    private function isEnabled(): bool
    {
        return !empty($this->key);
    }

    private function getCipherInstance(?Cipher $cipher = null): CipherInterface
    {
        return ($cipher ?? Cipher::default())->getInstance();
    }
}
