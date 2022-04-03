<?php

namespace Tests;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Spiral\Testing\Http\FakeHttp as BaseFakeHttp;

class FakeHttp extends BaseFakeHttp
{
    protected function createJsonRequest(
        string $uri,
        string $method,
        array $data,
        array $headers,
        array $cookies
    ): ServerRequest {
        $content = \json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        $body = fopen('php://temp', 'r+');
        fwrite($body, $content);

        return $this->createRequest($uri, $method, [], $headers, $cookies)
                    ->withBody(new Stream($body));
    }
}
