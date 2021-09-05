<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Charge;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="chargeView")
 */
class ChargeView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\Charge $charge
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(Charge $charge): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($charge),
        ], 200);
    }

    /**
     * @param \App\Database\Charge $charge
     * @return array
     */
    public function map(Charge $charge): array
    {
        return [
            'type'        => 'charge',
            'id'          => $charge->id,
            'operation'   => $charge->type,
            'amount'      => $charge->amount,
            'title'       => $charge->title,
            'description' => $charge->description,
            'userId'      => $charge->userId,
            'user'        => $this->userView->map($charge->user),
            'walletId'    => $charge->walletId,
            'createdAt'   => $charge->createdAt->format(DATE_W3C),
            'updatedAt'   => $charge->updatedAt->format(DATE_W3C),
        ];
    }
}
