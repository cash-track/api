<?php

declare(strict_types=1);

namespace App\View;

use App\Database\EmailConfirmation;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class EmailConfirmationView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
    ) {
    }

    public function json(EmailConfirmation $confirmation): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($confirmation),
        ], 200);
    }

    public function map(?EmailConfirmation $confirmation): ?array
    {
        if ($confirmation === null) {
            return null;
        }

        return [
            'type'      => 'emailConfirmation',
            'email'     => $confirmation->email,
            'createdAt' => $confirmation->createdAt->format(DATE_W3C),
        ];
    }
}
