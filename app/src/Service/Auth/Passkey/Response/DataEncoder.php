<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey\Response;

trait DataEncoder
{
    protected static function encode(array $data = []): string
    {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        return base64_encode((string) json_encode(value: $data, flags: JSON_THROW_ON_ERROR));
    }

    protected static function decode(string $data): string
    {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        return (string) base64_decode($data);
    }
}
