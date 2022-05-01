<?php

declare(strict_types=1);

namespace Tests\Feature\Request\Profile;

use App\Request\RefreshTokenRequest;
use Tests\Fixtures;
use Tests\TestCase;

class RefreshTokenRequestTest extends TestCase
{
    public function testGetAccessToken(): void
    {
        $token = Fixtures::string(256);

        $request = $this->getMockBuilder(RefreshTokenRequest::class)
                        ->disableOriginalConstructor()
                        ->onlyMethods(['getField'])
                        ->getMock();

        $request->method('getField')->with('accessToken')->willReturn($token);

        $this->assertEquals($token, $request->getAccessToken());
    }
}
