<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class PasskeysView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
        protected PasskeyView $passkeyView,
    ) {
    }

    public function json(array $passkeys): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($passkeys),
        ], 200);
    }

    public function map(array $passkeys): array
    {
        return array_map([$this->passkeyView, 'map'], $passkeys);
    }
}
