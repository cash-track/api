<?php

declare(strict_types=1);

namespace App\Database\Typecast;

use App\Service\Encrypter\Cipher;
use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\UncastableInterface;
use Psr\Log\LoggerInterface;
use App\Service\Encrypter\EncrypterInterface;
use Spiral\Encrypter\Exception\EncrypterException;

final class EncryptedTypecast implements CastableInterface, UncastableInterface
{
    // Encrypts database column using cipher which produces idempotent encrypted values
    // which can be used in WHERE-like clauses against encrypted columns.
    const string QUERY = 'encrypted-query';

    // Encrypts database column using strong encryption cipher that does not produce idempotent
    // encrypted values and cannot be queries in WHERE-like clauses
    const string STORE = 'encrypted-store';

    const array CIPHERS = [
        self::QUERY => Cipher::AES256ECB,
        self::STORE => Cipher::AES256GCM,
    ];

    private array $rules = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EncrypterInterface $encrypter,
    ) {
    }

    /**
     * @param array<non-empty-string, mixed> $rules
     * @return array<non-empty-string, mixed>
     */
    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if ($rule !== self::QUERY && $rule !== self::STORE) {
                continue;
            }

            unset($rules[$key]);

            $this->rules[$key] = $rule;
        }

        return $rules;
    }

    public function cast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (! isset($data[$column]) || !is_string($data[$column])) {
                continue;
            }

            try {
                $data[$column] = $this->encrypter->decrypt($data[$column], $this->getCipherByRule($rule));
            } catch (EncrypterException $exception) {
                $original = $data[$column];

                $data[$column] = '';

                if (empty($original)) {
                    continue;
                }

                $this->logger->warning('Unable to decrypt database column', [
                    'column' => $column,
                    'message' => $exception->getMessage(),
                    'value' => $original,
                ]);
            }
        }

        return $data;
    }

    public function uncast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (! isset($data[$column]) || !is_string($data[$column]) || empty($data[$column])) {
                continue;
            }

            try {
                $data[$column] = $this->encrypter->encrypt($data[$column], $this->getCipherByRule($rule));
            } catch (EncrypterException $exception) {
                $this->logger->warning('Unable to encrypt database column', [
                    'column' => $column,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $data;
    }

    protected function getCipherByRule(string $rule): ?Cipher
    {
        return static::CIPHERS[$rule] ?? null;
    }
}
