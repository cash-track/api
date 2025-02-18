<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Passkey;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
final class PasskeyView
{
    public function __construct(
        protected ResponseWrapper $response,
    ) {
    }

    public function json(Passkey $passkey): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($passkey),
        ]);
    }

    public function map(?Passkey $passkey): ?array
    {
        if ($passkey === null) {
            return null;
        }

        return [
            'type'      => 'passkey',
            'id'        => $passkey->id,
            'name'      => $passkey->name,
            'createdAt' => $passkey->createdAt->format(DATE_W3C),
            'usedAt'    => $passkey->usedAt?->format(DATE_W3C),
        ];
    }
}
