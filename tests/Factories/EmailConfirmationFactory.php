<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\EmailConfirmation;
use App\Service\Auth\EmailConfirmationService;
use Tests\Fixtures;

class EmailConfirmationFactory extends AbstractFactory
{
    public function create(EmailConfirmation $confirmation = null): EmailConfirmation
    {
        $confirmation = $confirmation ?? self::make();

        $this->persist($confirmation);

        return $confirmation;
    }

    public static function make(): EmailConfirmation
    {
        $confirmation = new EmailConfirmation();

        $confirmation->email = Fixtures::email();
        $confirmation->token = sha1(Fixtures::string());
        $confirmation->createdAt = Fixtures::dateTime();

        return $confirmation;
    }

    public static function notThrottled(EmailConfirmation $confirmation = null): EmailConfirmation
    {
        return self::notExpired($confirmation);
    }

    public static function throttled(EmailConfirmation $confirmation = null): EmailConfirmation
    {
        if ($confirmation === null) {
            $confirmation = self::make();
        }

        $confirmation->createdAt = Fixtures::dateTimeWithinTTL(
            EmailConfirmationService::RESEND_TIME_LIMIT,
        );

        return $confirmation;
    }

    public static function notExpired(EmailConfirmation $confirmation = null): EmailConfirmation
    {
        if ($confirmation === null) {
            $confirmation = self::make();
        }

        $confirmation->createdAt = Fixtures::dateTimeWithinTTL(
            EmailConfirmationService::TTL,
            EmailConfirmationService::RESEND_TIME_LIMIT, // and not throttled
        );

        return $confirmation;
    }

    public static function expired(EmailConfirmation $confirmation = null): EmailConfirmation
    {
        if ($confirmation === null) {
            $confirmation = self::make();
        }

        $confirmation->createdAt = Fixtures::dateTimeWithoutTTL(
            EmailConfirmationService::TTL,
        );

        return $confirmation;
    }
}
