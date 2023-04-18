<?php

declare(strict_types=1);

namespace App\Database\Encrypter;

interface EncrypterInterface
{
    public function encrypt(string $value): string;

    public function decrypt(string $payload): string;
}
