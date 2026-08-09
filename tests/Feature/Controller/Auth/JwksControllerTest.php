<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use Tests\TestCase;

class JwksControllerTest extends TestCase
{
    public function testReturnsEmptyKeySetWhenAccessKeypairNotConfigured(): void
    {
        $response = $this->get('/.well-known/jwks.json');

        $response->assertOk();

        $this->assertEquals(['keys' => []], $this->getJsonResponseBody($response));
    }

    public function testDoesNotRequireAuthentication(): void
    {
        $response = $this->get('/.well-known/jwks.json');

        $response->assertOk();
    }
}
