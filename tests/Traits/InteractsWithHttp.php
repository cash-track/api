<?php

declare(strict_types=1);

namespace Tests\Traits;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\ServerRequest;

trait InteractsWithHttp
{
    protected $defaultHeaders = [
        'Accept-Language' => 'en',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    protected function getHeaders(array $headers = []): array
    {
        return array_merge($headers, $this->defaultHeaders);
    }

    public function get(
        $uri,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $this->http->handle($this->request($uri, 'GET', $query, $headers, $cookies));
    }

    public function getWithAttributes(
        $uri,
        array $attributes,
        array $headers = []
    ): ResponseInterface {
        $r = $this->request($uri, 'GET', [], $headers, []);
        foreach ($attributes as $k => $v) {
            $r = $r->withAttribute($k, $v);
        }

        return $this->http->handle($r);
    }


    public function post(
        $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $this->http->handle(
            $this->request($uri, 'POST', [], $headers, $cookies)->withParsedBody($data)
        );
    }

    public function request(
        $uri,
        string $method,
        array $query = [],
        array $headers = [],
        array $cookies = []
    ): ServerRequest {
        return new ServerRequest(
            [],
            [],
            $uri,
            $method,
            'php://input',
            $this->getHeaders($headers),
            $cookies,
            $query
        );
    }

    public function fetchCookies(array $header)
    {
        $result = [];
        foreach ($header as $line) {
            $cookie = explode('=', $line);
            $result[$cookie[0]] = rawurldecode(substr($cookie[1], 0, strpos($cookie[1], ';')));
        }

        return $result;
    }

    public function getResponseBody(ResponseInterface $response): string {
        $body = $response->getBody();

        $body->rewind();

        return $body->getContents();
    }

    public function getJsonResponseBody(ResponseInterface $response):? array
    {
        try {
            $data = json_decode($this->getResponseBody($response), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->assertNotEmpty($exception->getMessage(), "Body:\n{$this->getResponseBody($response)}");
            return null;
        }

        if (is_array($data)) {
            return $data;
        }

        return null;
    }
}
