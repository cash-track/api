<?php

namespace Tests;

use Laminas\Diactoros\Stream;
use Nyholm\Psr7\ServerRequest;
use Spiral\Testing\Http\FakeHttp as BaseFakeHttp;
use Spiral\Testing\Http\TestResponse;

class FakeHttp extends BaseFakeHttp
{
    protected function createJsonRequest(
        string $uri,
        string $method,
        $data,
        array $headers,
        array $cookies,
        array $files = [],
        array $query = [],
    ): ServerRequest {
        $content = \json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        $body = fopen('php://temp', 'r+');
        fwrite($body, $content);

        return $this->createRequest($uri, $method, $query, $headers, $cookies, $files)
                    ->withBody(new Stream($body));
    }

    public function getJson(string $uri, array $query = [], array $headers = [], array $cookies = []): TestResponse
    {
        return $this->handleRequest(
            $this->createJsonRequest($uri, 'GET', [], $headers, $cookies, [], $query)
        );
    }

    public function patchJson(string $uri, $data = [], array $headers = [], array $cookies = [], array $files = []): TestResponse
    {
        return $this->handleRequest(
            $this->createJsonRequest($uri, 'PATCH', $data, $headers, $cookies)
        );
    }

    public function optionsJson(string $uri, array $headers = [], array $cookies = []): TestResponse
    {
        return $this->handleRequest(
            $this->createJsonRequest($uri, 'OPTIONS', [], $headers, $cookies)
        );
    }
}
