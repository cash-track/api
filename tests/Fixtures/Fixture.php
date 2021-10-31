<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DateInterval;
use DateTimeImmutable;

class Fixture
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

    public static function boolean(): bool
    {
        return (bool) rand(0, 1);
    }

    public static function dateTime(): DateTimeImmutable
    {
        return self::dateTimeWithin(
            (new DateTimeImmutable())->sub(new DateInterval('P2Y')),
            new DateTimeImmutable(),
        );
    }

    public static function dateTimeBefore(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return self::dateTimeWithin($dateTime->sub(new DateInterval('P2Y')), $dateTime);
    }

    public static function dateTimeAfter(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return self::dateTimeWithin($dateTime, $dateTime->add(new DateInterval('P2Y')));
    }

    public static function dateTimeWithin(DateTimeImmutable $from, DateTimeImmutable $to): DateTimeImmutable
    {
        $fromTimestamp = $from->getTimestamp();
        $toTimestamp = $to->getTimestamp();

        if ($fromTimestamp > $toTimestamp) {
            $fromTimestamp = $to->getTimestamp();
            $toTimestamp = $from->getTimestamp();
        }

        return (new DateTimeImmutable())->setTimestamp(rand($fromTimestamp, $toTimestamp));
    }
}
