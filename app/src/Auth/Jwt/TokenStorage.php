<?php

declare(strict_types=1);

namespace App\Auth\Jwt;

use App\Config\JwtConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Redis;
use Spiral\Auth\Exception\TokenStorageException;
use Spiral\Auth\TokenInterface;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Core\Attribute\Singleton;

#[Singleton]
class TokenStorage implements TokenStorageInterface
{
    private const string BLACKLIST_PREFIX = 'blacklist:jti:';

    protected readonly int $ttl;

    public function __construct(
        protected readonly JwtConfig $config,
        protected readonly Redis $redis,
    ) {
        $this->assertKeyMaterialConfigured();

        $this->ttl = $this->resolveTtl();
    }

    #[\Override]
    public function load(string $id): ?TokenInterface
    {
        $alg = $this->peekAlgorithm($id);

        if ($alg === null) {
            return null;
        }

        $key = $this->getVerificationKey($alg);

        if ($key === null) {
            return null;
        }

        try {
            $payload = (array) JWT::decode($id, $key);
        } catch (\Throwable $exception) {
            return null;
        }

        if ($this->isBlacklisted((string) ($payload['jti'] ?? ''))) {
            return null;
        }

        return Token::fromPayload($id, $payload);
    }

    #[\Override]
    public function create(array $payload, ?\DateTimeInterface $expiresAt = null): TokenInterface
    {
        $now = time();
        $expire = $now + $this->ttl;

        if ($expiresAt !== null) {
            $expire = $expiresAt->getTimestamp();
        } else {
            $expiresAt = (new \DateTimeImmutable())->setTimestamp($expire);
        }

        $payload = array_merge($payload, [
            'iss' => $this->config->getIssuer(),
            'aud' => $this->config->getAudience(),
            'iat' => $now,
            'exp' => $expire,
            'jti' => bin2hex(random_bytes(16)),
        ]);

        $keyId = $this->getKeyId();

        $jwt = JWT::encode($payload, $this->getSigningKey(), $this->getSigningAlgorithm(), $keyId !== '' ? $keyId : null);

        return new Token($jwt, $payload, $expiresAt);
    }

    #[\Override]
    public function delete(TokenInterface $token): void
    {
        $jti = (string) ($token->getPayload()['jti'] ?? '');

        if ($jti === '') {
            return;
        }

        $expiresAt = $token->getExpiresAt();
        $ttl = $expiresAt !== null ? $expiresAt->getTimestamp() - time() : $this->ttl;

        if ($ttl <= 0) {
            return;
        }

        try {
            if (! $this->redis->isConnected()) {
                return;
            }

            $this->redis->setex(self::BLACKLIST_PREFIX . $jti, $ttl, '1');
        } catch (\Throwable $exception) {
            // Best effort: a Redis outage must not break logout.
        }
    }

    /** Falls back to HS256 until the RSA access keypair is provisioned, so this deploys as-is. */
    protected function getSigningAlgorithm(): string
    {
        return $this->hasAccessRsaKeypair() ? 'RS256' : 'HS256';
    }

    protected function getSigningKey(): string
    {
        return $this->hasAccessRsaKeypair() ? $this->config->getAccessPrivateKey() : $this->config->getSecret();
    }

    /**
     * Fixed allow-list, so the token's own `alg` header can never steer verification to the
     * wrong key. Blocks algorithm confusion: an RS256 token replayed as HS256 hits the HS256
     * branch, which never touches the public key.
     */
    protected function getVerificationKey(string $alg): ?Key
    {
        return match ($alg) {
            'RS256' => $this->hasAccessRsaKeypair() ? new Key($this->config->getAccessPublicKey(), 'RS256') : null,
            'HS256' => $this->config->getSecret() !== '' ? new Key($this->config->getSecret(), 'HS256') : null,
            default => null,
        };
    }

    protected function getKeyId(): string
    {
        return $this->hasAccessRsaKeypair() ? $this->config->getAccessKeyId() : '';
    }

    protected function resolveTtl(): int
    {
        return $this->config->getTtl();
    }

    protected function assertKeyMaterialConfigured(): void
    {
        if ($this->config->getSecret() === '' && ! $this->hasAccessRsaKeypair()) {
            throw new TokenStorageException('JWT secret and access token keypair are empty');
        }
    }

    private function hasAccessRsaKeypair(): bool
    {
        return $this->config->getAccessPrivateKey() !== '' && $this->config->getAccessPublicKey() !== '';
    }

    private function peekAlgorithm(string $jwt): ?string
    {
        $segments = explode('.', $jwt);

        if (count($segments) !== 3) {
            return null;
        }

        try {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode($segments[0]));
        } catch (\Throwable $exception) {
            return null;
        }

        if (! $header instanceof \stdClass || ! isset($header->alg) || ! is_string($header->alg)) {
            return null;
        }

        return $header->alg;
    }

    /** Fails open: the signature is already verified, so a Redis outage must not block auth. */
    private function isBlacklisted(string $jti): bool
    {
        if ($jti === '') {
            return false;
        }

        try {
            if (! $this->redis->isConnected()) {
                return false;
            }

            $exists = $this->redis->exists(self::BLACKLIST_PREFIX . $jti);

            return is_int($exists) && $exists > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
