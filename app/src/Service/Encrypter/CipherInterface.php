<?php

declare(strict_types=1);

namespace App\Service\Encrypter;

interface CipherInterface
{
    public function encrypt(string $value, string $key): string;

    public function decrypt(string $payload, string $key): string;
}
