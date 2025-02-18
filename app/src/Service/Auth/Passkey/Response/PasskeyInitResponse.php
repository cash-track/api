<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey\Response;

final class PasskeyInitResponse implements \JsonSerializable
{
    use DataEncoder;

    public function __construct(
        public string $challenge,
        public string $data,
    ) {
    }

    public static function create(string $challenge, array $data = []): self
    {
        return new self($challenge, self::encode($data));
    }

    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'challenge' => $this->challenge,
            'data' => $this->data,
        ];
    }
}
