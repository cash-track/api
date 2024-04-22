<?php

declare(strict_types=1);

namespace Feature\Service\Auth\Passkey;

use Tests\Feature\Controller\PasskeyServiceMocker;
use Tests\TestCase;

class PasskeyServiceTest extends TestCase
{
    use PasskeyServiceMocker;

    public function testGenerateChallenge(): void
    {
        $service = $this->makePasskeyAuthMock();

        $data = $service->initAuth();

        $this->assertNotEmpty($data->jsonSerialize());
    }
}
