<?php

declare(strict_types=1);

namespace App\Service\Encrypter;

interface EncrypterInterface
{
    public function encrypt(string $value, ?Cipher $cipher = null): string;

    public function decrypt(string $payload, ?Cipher $cipher = null): string;
}
