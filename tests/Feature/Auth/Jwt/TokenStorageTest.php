<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Jwt;

use App\Auth\Jwt\TokenStorage;
use App\Config\JwtConfig;
use Spiral\Auth\Exception\TokenStorageException;
use Tests\TestCase;

class TokenStorageTest extends TestCase
{
    public function testEmptySecretThrownException(): void
    {
        $config = $this->getMockBuilder(JwtConfig::class)->getMock();
        $config->method('getSecret')->willReturn('');

        $this->expectException(TokenStorageException::class);

        new TokenStorage($config);
    }
}
