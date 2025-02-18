<?php

declare(strict_types=1);

namespace App\Auth\Jwt;

use App\Auth\RefreshTokenStorageInterface;
use App\Config\JwtConfig;
use Spiral\Auth\Exception\TokenStorageException;
use Spiral\Core\Attribute\Singleton;

#[Singleton]
final class RefreshTokenStorage extends TokenStorage implements RefreshTokenStorageInterface
{
    /**
     * @var string
     */
    protected $alg = 'RS256';

    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * RefreshTokenStorage constructor.
     *
     * @param \App\Config\JwtConfig $config
     */
    public function __construct(JwtConfig $config)
    {
        parent::__construct($config);

        $this->ttl = $config->getRefreshTtl();
        $this->publicKey = $config->getPublicKey();

        if ($this->publicKey == '') {
            throw new TokenStorageException('JWT public key are empty');
        }

        $this->privateKey = $config->getPrivateKey();

        if ($this->privateKey == '') {
            throw new TokenStorageException('JWT private key are empty');
        }
    }

    /**
     * @return string
     */
    #[\Override]
    protected function getVerifyKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    #[\Override]
    protected function getSigningKey(): string
    {
        return $this->privateKey;
    }
}
