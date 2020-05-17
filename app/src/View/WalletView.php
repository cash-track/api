<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="walletView")
 */
class WalletView implements SingletonInterface
{
    use PrototypeTrait;

    public function map(Wallet $wallet): array
    {
        return [
            'wallet' => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'slug' => $wallet->slug,
                'isActive' => $wallet->isActive,
                'isPublic' => $wallet->isPublic,
                'isArchived' => $wallet->isArchived,
                'currency' => [
                    'code' => $wallet->defaultCurrency->code,
                    'name' => $wallet->defaultCurrency->name,
                    'char' => $wallet->defaultCurrency->char,
                ],
                'createdAt' => $wallet->createdAt->format(DATE_W3C),
                'updatedAt' => $wallet->updatedAt->format(DATE_W3C),
            ]
        ];
    }

    public function json(Wallet $wallet): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($wallet),
        ], 200);
    }
}
