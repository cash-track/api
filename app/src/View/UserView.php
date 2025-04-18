<?php

declare(strict_types=1);

namespace App\View;

use App\Database\User;
use App\Service\PhotoStorageService;
use App\Service\UserOptionsService;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
final class UserView
{
    public function __construct(
        protected ResponseWrapper $response,
        protected CurrencyView $currencyView,
        protected PhotoStorageService $photoStorageService,
        protected UserOptionsService $userOptionsService,
    ) {
    }

    public function json(User $user): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($user),
        ]);
    }

    public function head(User $user): array
    {
        return [
            'type' => 'user',
            'id'   => $user->id,
        ];
    }

    public function map(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'type'             => 'user',
            'id'               => $user->id,
            'name'             => $user->name,
            'lastName'         => $user->lastName,
            'nickName'         => $user->nickName,
            'email'            => $user->email,
            'isEmailConfirmed' => $user->isEmailConfirmed,
            'photoUrl'         => $this->photoStorageService->getProfilePhotoPublicUrl($user->photo),
            'createdAt'        => $user->createdAt->format(DATE_W3C),
            'updatedAt'        => $user->updatedAt->format(DATE_W3C),

            'defaultCurrencyCode' => $user->defaultCurrencyCode,
            'defaultCurrency'     => $this->currencyView->map($user->getDefaultCurrency()),

            'locale' => $this->userOptionsService->getLocale($user),
        ];
    }
}
