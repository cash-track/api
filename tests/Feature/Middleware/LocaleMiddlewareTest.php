<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Middleware\LocaleSelectorMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Tests\TestCase;

class LocaleMiddlewareTest extends TestCase
{
    public function testFetchLocalesMultiple(): void
    {
        $header = 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5';
        $headerLocales = ['fr-CH', 'fr', 'en', 'de', '*'];

        $middleware = $this->getContainer()->get(LocaleSelectorMiddleware::class);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $request->method('getHeaderLine')->with('accept-language')->willReturn($header);

        $locales = $middleware->fetchLocales($request);

        foreach ($locales as $locale) {
            $this->assertContains($locale, $headerLocales);
        }
    }
}
