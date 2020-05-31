<?php

declare(strict_types = 1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="walletsView")
 */
class WalletsView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\Wallet[] $wallets
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(array $wallets): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($wallets),
        ], 200);
    }

    /**
     * @param \App\Database\Wallet[] $wallets
     * @return array
     */
    public function map(array $wallets): array
    {
        return array_map([$this->walletView, 'map'], $wallets);
    }
}
