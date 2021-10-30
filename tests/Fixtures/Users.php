<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Database\User;

class Users extends Fixture
{
    public const DEFAULT_PASSWORD = 'secret';

    public static function default(): User
    {
        $user = new User;

        $user->name = ucfirst(self::string());
        $user->lastName = ucfirst(self::string());;
        $user->nickName = self::string();
        $user->email = self::email();
        $user->defaultCurrencyCode = Currencies::code();
        $user->password = password_hash(self::DEFAULT_PASSWORD, PASSWORD_ARGON2ID);
        $user->createdAt = self::dateTime();
        $user->updatedAt = self::dateTimeAfter($user->createdAt);
        $user->photo = self::fileName();
        $user->isEmailConfirmed = self::boolean();

        return self::withPassword(self::DEFAULT_PASSWORD, $user);
    }

    public static function withPassword(string $password, User $user = null): User
    {
        if ($user === null) {
            return self::default();
        }

        $user->password = password_hash($password, PASSWORD_ARGON2ID);

        return $user;
    }

    public static function emailConfirmed(User $user = null): User
    {
        if ($user === null) {
            $user = self::default();
        }

        $user->isEmailConfirmed = true;

        return $user;
    }
}
