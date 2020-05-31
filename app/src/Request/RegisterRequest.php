<?php

declare(strict_types=1);

namespace App\Request;

use App\Database\Currency;
use App\Database\User;
use Spiral\Filters\Filter;

class RegisterRequest extends Filter
{
    protected const SCHEMA = [
        'name' => 'data:name',
        'lastName' => 'data:lastName',
        'nickName' => 'data:nickName',
        'email' => 'data:email',
        'password' => 'data:password',
        'passwordConfirmation' => 'data:passwordConfirmation'
    ];

    protected const VALIDATES = [
        'name' => [
            'is_string',
            'type::notEmpty',
        ],
        'lastName' => [
            'is_string',
        ],
        'nickName' => [
            'is_string',
            'type::notEmpty',
            ['string::regexp', '/^[a-zA-Z0-9_]*$/'],
            ['entity::unique', User::class, 'nickName'],
        ],
        'email' => [
            'address::email',
            'type::notEmpty',
            ['entity::unique', User::class, 'email'],
        ],
        'password' => [
            'type::notEmpty',
            ['string::longer', 6],
        ],
        'passwordConfirmation' => [
            ['notEmpty', 'if' => ['withAll' => ['password']]],
            ['match', 'password', 'error' => 'Password confirmation does not match']
        ],
    ];

    public function createUser(): User
    {
        $user = new User();

        $user->name = $this->getField('name');
        $user->lastName = $this->getField('lastName');
        $user->nickName = $this->getField('nickName');
        $user->email = $this->getField('email');
        $user->defaultCurrencyCode = Currency::DEFAULT_CURRENCY_CODE;

        return $user;
    }
}
