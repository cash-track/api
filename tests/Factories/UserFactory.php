<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\User;
use App\Service\UserService;
use Tests\Fixtures;

class UserFactory extends AbstractFactory
{
    public const DEFAULT_PASSWORD = 'secret';

    public function __construct(protected UserService $storage)
    {
        //
    }

    public function create(User $user = null): User
    {
        $user = $user ?? self::make();

        $this->storage->store($user);

        return $user;
    }

    public static function make(): User
    {
        $user = new User;

        $user->name = ucfirst(strtolower(Fixtures::string()));
        $user->lastName = ucfirst(strtolower(Fixtures::string()));
        $user->nickName = Fixtures::string();
        $user->email = Fixtures::email();
        $user->defaultCurrencyCode = CurrencyFactory::code();
        $user->password = password_hash(self::DEFAULT_PASSWORD, PASSWORD_ARGON2ID);
        $user->createdAt = Fixtures::dateTime();
        $user->updatedAt = Fixtures::dateTimeAfter($user->createdAt);
        $user->photo = Fixtures::fileName();
        $user->isEmailConfirmed = Fixtures::boolean();

        return self::withPassword(self::DEFAULT_PASSWORD, $user);
    }

    public static function withPassword(string $password, User $user = null): User
    {
        if ($user === null) {
            return self::make();
        }

        $user->password = password_hash($password, PASSWORD_ARGON2ID);

        return $user;
    }

    public static function emailConfirmed(User $user = null): User
    {
        if ($user === null) {
            $user = self::make();
        }

        $user->isEmailConfirmed = true;

        return $user;
    }
}
