<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\BearerHeaderTransport;
use Psr\Http\Message\ServerRequestInterface;
use Tests\TestCase;

class BearerHeaderTransportTest extends TestCase
{
    public function testFetchTokenWrongHeaderFormat(): void
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('hasHeader')->with('Authorization')->willReturn(true);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('');

        $transport = new BearerHeaderTransport();
        $this->assertNull($transport->fetchToken($request));
    }
}
