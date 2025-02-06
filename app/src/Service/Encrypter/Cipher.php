<?php

declare(strict_types=1);

namespace App\Service\Encrypter;

use RuntimeException;

// FIXME. PHPCS PSR12 Does not support PHP 8.1 new feature syntax
// @codingStandardsIgnoreStart
enum Cipher: string
{
    case AES256ECB = AES256ECB::class;
    case AES256GCM = AES256GCM::class;

    public static function default(): self
    {
        return self::AES256ECB;
    }

    public function getInstance(): CipherInterface
    {
        class_exists($this->value) || throw new RuntimeException("Undefined cipher class {$this->value}");

        return new $this->value;
    }
}
// @codingStandardsIgnoreEnd
