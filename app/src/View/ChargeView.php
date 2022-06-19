<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Charge;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class ChargeView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
        protected UserView $userView,
        protected TagsView $tagsView,
    ) {
    }

    public function json(Charge $charge): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($charge),
        ]);
    }

    public function map(?Charge $charge): ?array
    {
        if ($charge === null) {
            return null;
        }

        return [
            'type'        => 'charge',
            'id'          => $charge->id,
            'operation'   => $charge->type,
            'amount'      => $charge->amount,
            'title'       => $charge->title,
            'description' => $charge->description,
            'userId'      => $charge->userId,
            'user'        => $this->userView->map($charge->getUser()),
            'walletId'    => $charge->walletId,
            'tags'        => $this->tagsView->map($charge->getTags()),
            'createdAt'   => $charge->createdAt->format(DATE_W3C),
            'updatedAt'   => $charge->updatedAt->format(DATE_W3C),
        ];
    }
}
