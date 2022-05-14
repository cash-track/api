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

    public function __construct(
       private LoggerInterface $logger
    ) {
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
     * @param array $values
     * @return array
     */
    public function cast(array $values): array
    {
        foreach ($this->rules as $column => $rule) {
            if (! isset($values[$column])) {
                continue;
            }

            try {
                $values[$column] = json_decode($values[$column], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $original = $values[$column];

                $values[$column] = [];

                // avoid unnecessary logs for empty string
                if (empty($original)) {
                    continue;
                }

                $this->logger->warning('Unable to decode database json', [
                    'column' => $column,
                    'message' => $exception->getMessage(),
                    'json' => $values[$column],
                ]);
            }

        }

        return $values;
    }

    /**
     * @param array $values
     * @return array
     */
    public function uncast(array $values): array
    {
        foreach ($this->rules as $column => $rule) {
            if (! isset($values[$column]) || !is_array($values[$column])) {
                continue;
            }

            try {
                $values[$column] = json_encode($values[$column], JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $this->logger->warning('Unable to encode json for database', [
                    'column' => $column,
                    'message' => $exception->getMessage(),
                    'json' => print_r($values[$column], true),
                ]);

                $values[$column] = '[]';
            }

        }

        return $values;
    }
}
