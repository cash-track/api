<?php

declare(strict_types=1);

namespace Tests\Traits;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Spiral\Testing\Http\FakeHttp;
use Spiral\Testing\Http\TestResponse;

trait InteractsWithHttp
{
    protected $defaultHeaders = [
        'Accept-Language' => 'en',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    protected array $authHeaders = [];

    protected function http(): FakeHttp
    {
        return $this->fakeHttp();
    }

    protected function getHeaders(array $headers = []): array
    {
        return array_merge($headers, $this->defaultHeaders, $this->authHeaders);
    }

    protected function injectQuery(string $uri, array $query): string
    {
        if (count($query) === 0) {
            return $uri;
        }

        return $uri . '?' . http_build_query($query);
    }

    public function get(
        $uri,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->http()->getJson($this->injectQuery($uri, $query), [], $this->getHeaders($headers), $cookies);
    }

    public function post(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->http()->postJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    public function put(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->http()->putJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    public function patch(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->http()->patchJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    public function delete(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        return $this->http()->deleteJson($uri, $data, $this->getHeaders($headers), $cookies);
    }

    /**
     * @throws \ReflectionException
     */
    public function optionsJson(
        $uri,
        array $headers = [],
        array $cookies = []
    ): TestResponse {
        // FIXME. This implements OPTIONS HTTP call over standard FakeHttp using reflection
        //        as extending FakeHttp not possible due to internal methods visibility.
        $http = $this->fakeHttp();
        $r = new \ReflectionClass($http);
        $response = $r->getMethod('handleRequest')->invoke(
            $http,
            $r->getMethod('createJsonRequest')->invoke($http, $uri, 'OPTIONS', [], $this->getHeaders($headers), $cookies)
        );
        if ($response instanceof TestResponse) {
            return $response;
        }

        throw new \ReflectionException('Unable to handle request with method OPTIONS');
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
