<?php

declare(strict_types = 1);

namespace App\View;

use App\Database\User;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="userView")
 */
class UserView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\User $user
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(User $user): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($user),
        ], 200);
    }

    /**
     * @param \App\Database\User $user
     * @return array
     */
    public function head(User $user): array
    {
        return [
            'type' => 'user',
            'id'   => $user->id,
        ];
    }

    /**
     * @param \App\Database\User $user
     * @return array
     */
    public function map(User $user): array
    {
        return [
            'type'      => 'user',
            'id'        => $user->id,
            'name'      => $user->name,
            'lastName'  => $user->lastName,
            'nickName'  => $user->nickName,
            'email'     => $user->email,
            'photoUrl'  => $user->photoUrl,
            'createdAt' => $user->createdAt->format(DATE_W3C),
            'updatedAt' => $user->updatedAt->format(DATE_W3C),

            'defaultCurrencyCode' => $user->defaultCurrencyCode,
            'defaultCurrency'     => $this->currencyView->map($user->defaultCurrency),
        ];
    }
}
