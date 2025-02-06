<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\GoogleAccount;
use App\Database\User;
use Tests\Fixtures;

class GoogleAccountFactory extends AbstractFactory
{
    public function create(?GoogleAccount $googleAccount = null): GoogleAccount
    {
        $googleAccount = $googleAccount ?? self::make();

        $this->persist($googleAccount);

        return $googleAccount;
    }

    public static function make(): GoogleAccount
    {
        $googleAccount = new GoogleAccount;

        $googleAccount->accountId = Fixtures::string();
        $googleAccount->pictureUrl = Fixtures::url();
        $googleAccount->setData([
            'sub' => $googleAccount->accountId,
            'picture' => $googleAccount->pictureUrl,
        ]);

        return $googleAccount;
    }

    public static function withUser(User $user, ?array $data = null): GoogleAccount
    {
        $googleAccount = $data === null ? self::make() : self::withData($data);
        $googleAccount->userId = $user->id;

        return $googleAccount;
    }

    public static function withData(array $data = []): GoogleAccount
    {
        $googleAccount = self::make();
        $googleAccount->accountId = $data['sub'] ?? null;
        $googleAccount->pictureUrl = $data['picture'] ?? null;
        $googleAccount->setData($data);

        return $googleAccount;
    }
}
