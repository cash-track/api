<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Jwt;

use App\Auth\Jwt\RefreshTokenStorage;
use App\Config\JwtConfig;
use Spiral\Auth\Exception\TokenStorageException;
use Tests\TestCase;

class RefreshTokenStorageTest extends TestCase
{
    public function testEmptyPublicKeyThrownException(): void
    {
        $config = $this->getMockBuilder(JwtConfig::class)->getMock();
        $config->method('getSecret')->willReturn('123');
        $config->method('getRefreshTtl')->willReturn(1);
        $config->method('getPublicKey')->willReturn('');

        $this->expectException(TokenStorageException::class);

        new RefreshTokenStorage($config);
    }

    public function testEmptyPrivateKeyThrownException(): void
    {
        $config = $this->getMockBuilder(JwtConfig::class)->getMock();
        $config->method('getSecret')->willReturn('123');
        $config->method('getRefreshTtl')->willReturn(1);
        $config->method('getPublicKey')->willReturn('123');
        $config->method('getPrivateKey')->willReturn('');

        $this->expectException(TokenStorageException::class);

        new RefreshTokenStorage($config);
    }
}
