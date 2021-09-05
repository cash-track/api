<?php

declare(strict_types=1);

namespace App\View;

use App\Database\EmailConfirmation;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="emailConfirmationView")
 */
class EmailConfirmationView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\EmailConfirmation $confirmation
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(EmailConfirmation $confirmation): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($confirmation),
        ], 200);
    }

    /**
     * @param \App\Database\EmailConfirmation $confirmation
     * @return array
     */
    public function map(EmailConfirmation $confirmation): array
    {
        return [
            'type'      => 'emailConfirmation',
            'email'     => $confirmation->email,
            'createdAt' => $confirmation->createdAt->format(DATE_W3C),
        ];
    }
}
