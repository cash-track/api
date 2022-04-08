<?php

declare(strict_types=1);

namespace Tests\Fixtures;

trait BasicFixtures
{
    public static function string(int $length = 6): string
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $pieces = [];

        $max = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = $keyspace[rand(0, $max)];
        }

        return implode('', $pieces);
    }

    public static function arrayElement(array $arr): mixed
    {
        $keys = array_keys($arr);

        if (count($keys) === 0) {
            return null;
        }

        $keyIndex = rand(0, count($keys) - 1);

        return $arr[$keys[$keyIndex]] ?? null;
    }

    public static function email(): string
    {
        return self::string() . '@' . self::string() . '.com';
    }

    public static function fileName(string $extension = 'png'): string
    {
        return self::string(16) . '.' . $extension;
    }

    public static function url(string $ends = ''): string
    {
        return "https://" . self::string() . '.com/' . self::string() . '/' . $ends;
    }

    public static function boolean(): bool
    {
        return (bool) rand(0, 1);
    }

    public static function integer(int $min = 0, int $max = 100): int
    {
        return rand($min, $max);
    }
}
