<?php

declare(strict_types=1);

namespace App\Database\Typecast;

use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\UncastableInterface;
use Psr\Log\LoggerInterface;

final class JsonTypecast implements CastableInterface, UncastableInterface
{
    const RULE = 'json';

    private array $rules = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param array<non-empty-string, mixed> $rules
     * @return array<non-empty-string, mixed>
     */
    public function setRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            if ($rule !== self::RULE) {
                continue;
            }

            unset($rules[$key]);

            $this->rules[$key] = $rule;
        }

        return $rules;
    }

    /**
     * @param array $data
     * @return array
     */
    public function cast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (! isset($data[$column]) || !is_string($data[$column])) {
                continue;
            }

            try {
                $data[$column] = json_decode($data[$column], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $original = $data[$column];

                $data[$column] = [];

                // avoid unnecessary logs for empty string
                if (empty($original)) {
                    continue;
                }

                $this->logger->warning('Unable to decode database json', [
                    'column' => $column,
                    'message' => $exception->getMessage(),
                    'json' => $data[$column],
                ]);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function uncast(array $data): array
    {
        foreach ($this->rules as $column => $rule) {
            if (! isset($data[$column]) || !is_array($data[$column])) {
                continue;
            }

            try {
                $data[$column] = json_encode($data[$column], JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $this->logger->warning('Unable to encode json for database', [
                    'column' => $column,
                    'message' => $exception->getMessage(),
                    'json' => print_r($data[$column], true),
                ]);

                $data[$column] = '[]';
            }
        }

        return $data;
    }
}
