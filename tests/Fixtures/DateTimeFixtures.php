<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DateInterval;
use DateTimeImmutable;

trait DateTimeFixtures
{
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

    /**
     * @param int $ttl TTL in seconds
     * @param int $shift Sub end range to seconds
     * @return \DateTimeImmutable
     */
    public static function dateTimeWithinTTL(int $ttl, int $shift = 0): DateTimeImmutable
    {
        $ttl -= 1; // to make sure we're within

        return self::dateTimeWithin(
            (new DateTimeImmutable())->sub(new DateInterval("PT{$ttl}S")),
            (new DateTimeImmutable())->sub(new DateInterval("PT{$shift}S")),
        );
    }

    /**
     * @param int $ttl TTL in seconds
     * @return \DateTimeImmutable
     */
    public static function dateTimeWithoutTTL(int $ttl): DateTimeImmutable
    {
        $ttl += 1; // to make sure we're without
        $ttlFrom = $ttl * 2;

        return self::dateTimeWithin(
            (new DateTimeImmutable())->sub(new DateInterval("PT{$ttlFrom}S")),
            (new DateTimeImmutable())->sub(new DateInterval("PT{$ttl}S")),
        );
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
