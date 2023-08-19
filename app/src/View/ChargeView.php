<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Charge;
use App\Database\Tag;
use App\Database\User;
use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class ChargeView implements SingletonInterface
{
    use Relations;

    public function __construct(
        protected ResponseWrapper $response,
        protected UserShortView $userShortView,
        protected TagsView $tagsView,
        protected WalletShortView $walletShortView,
    ) {
        $this->withRelations([User::class, Tag::class]);
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
            'walletId'    => $charge->walletId,
            'dateTime'    => $charge->createdAt->format(DATE_W3C),
            'createdAt'   => $charge->createdAt->format(DATE_W3C),
            'updatedAt'   => $charge->updatedAt->format(DATE_W3C),

            'user'        => $this->loaded(User::class) ? $this->userShortView->map($charge->getUser()) : null,
            'tags'        => $this->loaded(Tag::class) ? $this->tagsView->map($charge->getTags()) : [],
            'wallet'      => $this->loaded(Wallet::class) ? $this->walletShortView->map($charge->getWallet()) : null,
        ];
    }
}
