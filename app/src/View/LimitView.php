<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Limit;
use App\Database\Tag;
use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
final class LimitView
{
    use Relations;

    public function __construct(
        protected ResponseWrapper $response,
        protected TagsView $tagsView,
        protected WalletShortView $walletShortView,
    ) {
        $this->withRelations([Tag::class]);
    }

    public function json(Limit $limit): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($limit),
        ]);
    }

    public function map(?Limit $limit): ?array
    {
        if ($limit === null) {
            return null;
        }

        return [
            'type'        => 'limit',
            'id'          => $limit->id,
            'operation'   => $limit->type,
            'amount'      => $limit->amount,
            'walletId'    => $limit->walletId,
            'createdAt'   => $limit->createdAt->format(DATE_W3C),
            'updatedAt'   => $limit->updatedAt->format(DATE_W3C),

            'tags'        => $this->loaded(Tag::class) ? $this->tagsView->map($limit->getTags()) : [],
            'wallet'      => $this->loaded(Wallet::class) ? $this->walletShortView->map($limit->getWallet()) : null,
        ];
    }
}
