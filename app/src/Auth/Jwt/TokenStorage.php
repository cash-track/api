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
    protected $secret;

    /**
     * @var string
     */
    protected $alg = 'HS256';

    /**
     * @var int
     */
    protected $ttl;

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
        // TODO. Validate token for the blacklisted

        try {
            $payload = JWT::decode($id, $this->getVerifyKey(), [$this->alg]);
            return Token::fromPayload($id, (array) $payload);
        } catch (\Throwable $exception) {
            return null;
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


        $jwt = JWT::encode($payload, $this->getSigningKey(), $this->alg);

        return new Token((string) $jwt, $payload, $expiresAt);
    }

    /**
     * @inheritDoc
     */
    public function delete(TokenInterface $token): void
    {
        // TODO: Implement delete() method. Add token to the blacklist
    }

    /**
     * @return string
     */
    protected function getVerifyKey(): string
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    protected function getSigningKey(): string
    {
        return $this->secret;
    }
}
