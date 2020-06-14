<?php

declare(strict_types = 1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="chargesView")
 */
class ChargesView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\Charge[] $charges
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(array $charges): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($charges),
        ], 200);
    }

    /**
     * @param \App\Database\Charge[] $charges
     * @return array
     */
    public function map(array $charges): array
    {
        return array_map([$this->chargeView, 'map'], $charges);
    }
}
