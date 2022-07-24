<?php

declare(strict_types=1);

namespace App\View;

use App\Database\User;
use App\Service\PhotoStorageService;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class UserShortView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
        protected PhotoStorageService $photoStorageService,
    ) {
    }

    public function json(User $user): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($user),
        ]);
    }

    public function map(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'type'     => 'userShort',
            'id'       => $user->id,
            'name'     => $user->name,
            'lastName' => $user->lastName,
            'nickName' => $user->nickName,
            'photoUrl' => $this->photoStorageService->getProfilePhotoPublicUrl($user->photo),
        ];
    }
}
