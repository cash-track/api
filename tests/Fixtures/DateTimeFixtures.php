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
