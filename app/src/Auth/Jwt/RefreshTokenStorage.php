<?php

declare(strict_types=1);

namespace App\Auth\Jwt;

use App\Auth\RefreshTokenStorageInterface;
use Firebase\JWT\Key;
use Spiral\Auth\Exception\TokenStorageException;
use Spiral\Core\Attribute\Singleton;

#[Singleton]
final class RefreshTokenStorage extends TokenStorage implements RefreshTokenStorageInterface
{
    #[\Override]
    protected function getSigningAlgorithm(): string
    {
        return 'RS256';
    }

    #[\Override]
    protected function getSigningKey(): string
    {
        return $this->config->getPrivateKey();
    }

    #[\Override]
    protected function getVerificationKey(string $alg): ?Key
    {
        if ($alg !== 'RS256') {
            return null;
        }

        return new Key($this->config->getPublicKey(), 'RS256');
    }

    #[\Override]
    protected function getKeyId(): string
    {
        // Signed with a dedicated keypair, never published via the access token JWKS endpoint.
        return '';
    }

    #[\Override]
    protected function resolveTtl(): int
    {
        return $this->config->getRefreshTtl();
    }

    #[\Override]
    protected function assertKeyMaterialConfigured(): void
    {
        if ($this->config->getPublicKey() === '') {
            throw new TokenStorageException('JWT public key are empty');
        }

        if ($this->config->getPrivateKey() === '') {
            throw new TokenStorageException('JWT private key are empty');
        }
    }
}
