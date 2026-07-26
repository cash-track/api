<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use App\Exception\AuthenticationRequiredException;
use App\Exception\UnconfirmedProfileException;
use App\Exception\ViewRenderer;
use Laminas\Diactoros\ServerRequest;
use Tests\TestCase;

class ViewRendererTest extends TestCase
{
    private ViewRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = $this->getContainer()->get(ViewRenderer::class);
    }

    public function renderExceptionDataProvider(): array
    {
        return [
            'authentication required' => [new AuthenticationRequiredException('Authentication required.'), 500, 401],
            'unconfirmed profile' => [new UnconfirmedProfileException('Profile is not confirmed.'), 500, 403],
            'unmapped exception keeps the given code' => [new \RuntimeException('Boom.'), 404, 404],
        ];
    }

    /**
     * @dataProvider renderExceptionDataProvider
     */
    public function testRenderException(\Throwable $exception, int $code, int $expectedCode): void
    {
        $response = $this->renderer->renderException(
            new ServerRequest(uri: '/profile', method: 'GET'),
            $code,
            $exception,
        );

        $this->assertEquals($expectedCode, $response->getStatusCode());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals($expectedCode, $body['status']);
        $this->assertEquals($exception->getMessage(), $body['error']);
    }
}
