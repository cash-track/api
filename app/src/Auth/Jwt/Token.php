<?php

declare(strict_types=1);

namespace App\Auth\Jwt;

use Spiral\Auth\TokenInterface;

class Token implements TokenInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTimeInterface|null
     */
    private $expiresAt;

    /**
     * @var array
     */
    private $payload;

    /**
     * @param string $id
     * @param array $payload
     * @param \DateTimeInterface|null $expiresAt
     */
    public function __construct(string $id, array $payload, \DateTimeInterface $expiresAt = null)
    {
        $this->id = $id;
        $this->expiresAt = $expiresAt;
        $this->payload = $payload;
    }

    /**
     * @inheritDoc
     */
    public function getID(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param string $id
     * @param array $payload
     * @return Token
     */
    public static function fromPayload(string $id, array $payload): Token
    {
        $expiresAt = null;
        if ($payload['exp'] ?? null !== null) {
            $expiresAt = (new \DateTimeImmutable())->setTimestamp($payload['exp']);
        }

        return new self($id, $payload, $expiresAt);
    }
}
