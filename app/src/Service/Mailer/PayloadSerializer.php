<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use ReflectionClass;

trait PayloadSerializer
{
    public static function fromPayload(array $payload): object
    {
        $class = $payload['class'] ?? self::class;

        if (empty($payload['attrs'])) {
            return new $class();
        }

        foreach ($payload['attrs'] as $key => $value) {
            if (is_array($value) && array_key_exists('class', $value)) {
                $payload['attrs'][$key] = static::fromPayload($value);
            }
        }

        return new $class(...$payload['attrs']);
    }

    public function toPayload(): array
    {
        $payload = [
            'class' => get_class($this),
            'attrs' => [],
        ];

        $rf = new ReflectionClass($this);

        foreach ($rf->getConstructor()?->getParameters() ?? [] as $parameter) {
            if (is_object($this->{$parameter->name}) && method_exists($this->{$parameter->name}, 'toPayload')) {
                $payload['attrs'][] = $this->{$parameter->name}->toPayload();
                continue;
            }

            $payload['attrs'][] = $this->{$parameter->name};
        }

        return $payload;
    }
}
