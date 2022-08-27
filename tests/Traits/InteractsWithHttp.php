<?php

declare(strict_types=1);

namespace Tests\Traits;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Spiral\Testing\Http\TestResponse;
use Tests\FakeHttp;

trait InteractsWithHttp
{
    protected $defaultHeaders = [
        'Accept-Language' => 'en',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    protected array $authHeaders = [];

    protected function fakeHttp(): FakeHttp
    {
        return new FakeHttp(
            $this->getContainer(),
            $this->getFileFactory(),
            function (\Closure $closure, array $bindings = []) {
                return $this->runScoped($closure, $bindings);
            }
        );
    }

    protected function getHeaders(array $headers = []): array
    {
        return array_merge($headers, $this->defaultHeaders, $this->authHeaders);
    }

    public function get(
        $uri,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->fakeHttp()->getJson($uri, $query, $this->getHeaders($headers), $cookies);
    }

    public function post(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->fakeHttp()->postJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    public function put(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->fakeHttp()->putJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    public function patch(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->fakeHttp()->patchJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    public function delete(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->fakeHttp()->deleteJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    public function getResponseBody(TestResponse|ResponseInterface $response): string
    {
        if ($response instanceof TestResponse) {
            return (string) $response->getOriginalResponse()->getBody();
        }

        return (string) $response->getBody();
    }

    public function getJsonResponseBody(TestResponse|ResponseInterface $response): array
    {
        try {
            $data = json_decode($this->getResponseBody($response), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->assertNotEmpty($exception->getMessage(), "Body:\n{$this->getResponseBody($response)}");
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        return [];
    }

    public function makeAuthHeadersByResponse(array $tokens = [], string $type = 'accessToken'): array
    {
        if (($tokens[$type] ?? null) === null) {
            return [];
        }

        return [
            'Authorization' => "Bearer {$tokens[$type]}",
        ];
    }

    public function withAuth(array $body): self
    {
        $this->authHeaders = $this->makeAuthHeadersByResponse($body);

        return $this;
    }

    public function withAuthRefresh(array $body): self
    {
        $this->authHeaders = $this->makeAuthHeadersByResponse($body, 'refreshToken');

        return $this;
    }

    public function resetAuth(): void
    {
        $this->authHeaders = [];
    }
}
