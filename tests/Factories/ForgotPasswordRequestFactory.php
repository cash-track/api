<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\ForgotPasswordRequest;
use App\Service\Auth\ForgotPasswordService;
use Tests\Fixtures;

class ForgotPasswordRequestFactory extends AbstractFactory
{
    public function create(ForgotPasswordRequest $request = null): ForgotPasswordRequest
    {
        $request = $request ?? self::make();

        $this->persist($request);

        return $request;
    }

    public static function make(): ForgotPasswordRequest
    {
        $request = new ForgotPasswordRequest();

        $request->email = Fixtures::email();
        $request->code = sha1(Fixtures::string());
        $request->createdAt = Fixtures::dateTime();

        return $request;
    }

    public static function notThrottled(ForgotPasswordRequest $request = null): ForgotPasswordRequest
    {
        return self::notExpired($request);
    }

    public static function throttled(ForgotPasswordRequest $request = null): ForgotPasswordRequest
    {
        if ($request === null) {
            $request = self::make();
        }

        $request->createdAt = Fixtures::dateTimeWithinTTL(
            ForgotPasswordService::RESEND_TIME_LIMIT,
        );

        return $request;
    }

    public static function notExpired(ForgotPasswordRequest $request = null): ForgotPasswordRequest
    {
        if ($request === null) {
            $request = self::make();
        }

        $request->createdAt = Fixtures::dateTimeWithinTTL(
            ForgotPasswordService::TTL,
            ForgotPasswordService::RESEND_TIME_LIMIT, // and not throttled
        );

        return $request;
    }

    public static function expired(ForgotPasswordRequest $request = null): ForgotPasswordRequest
    {
        if ($request === null) {
            $request = self::make();
        }

        $request->createdAt = Fixtures::dateTimeWithoutTTL(
            ForgotPasswordService::TTL,
        );

        return $request;
    }
}
