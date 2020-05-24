<?php

declare(strict_types=1);

namespace App\Auth\Jwt;

use App\Config\JwtConfig;
use Firebase\JWT\JWT;
use Spiral\Auth\Exception\TokenStorageException;
use Spiral\Auth\TokenInterface;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Core\Container\SingletonInterface;

class TokenStorage implements TokenStorageInterface, SingletonInterface
{
    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $alg = 'HS256';

    /**
     * @var int
     */
    private $ttl;

    /**
     * TokenStorage constructor.
     *
     * @param \App\Config\JwtConfig $config
     */
    public function __construct(JwtConfig $config)
    {
        $this->secret = $config->getSecret();

        if ($this->secret == '') {
            throw new TokenStorageException('JWT secret are empty');
        }

        $this->ttl = $config->getTtl();
    }

    /**
     * @inheritDoc
     */
    public function load(string $id): ?TokenInterface
    {
        try {
            $payload = JWT::decode($id, $this->secret, [$this->alg]);
            return Token::fromPayload($id, (array) $payload);
        } catch (\Throwable $exception) {
            throw new TokenStorageException('Invalid token', $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function create(array $payload, \DateTimeInterface $expiresAt = null): TokenInterface
    {
        $now = time();
        $expire = $now + $this->ttl;

        if ($expiresAt !== null) {
            $expire = $expiresAt->getTimestamp();
        } else {
            $expiresAt = (new \DateTimeImmutable())->setTimestamp($expire);
        }

        // TODO. Prevent hardcoded data, resolve those values somehow

        $payload = array_merge($payload, [
            'iss' => 'https://api.cash-track.ml',
            'aud' => 'https://api.cash-track.ml',
            'iat' => $now,
            'exp' => $expire,
        ]);


        $jwt = JWT::encode($payload, $this->secret, $this->alg);

        return new Token((string) $jwt, $payload, $expiresAt);
    }

    /**
     * @inheritDoc
     */
    public function delete(TokenInterface $token): void
    {
        // TODO: Implement delete() method.
    }
}
