<?php

declare(strict_types=1);

namespace App\Request;

use App\Database\Currency;
use App\Database\User;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class RegisterRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $name = '';

    #[Data]
    public string $lastName = '';

    #[Data]
    public string $nickName = '';

    #[Data]
    public string $email = '';

    #[Data]
    public string $password = '';

    #[Data]
    public string $passwordConfirmation = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
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
                ['string::longer', 3],
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
        ]);
    }

    public function createUser(): User
    {
        $user = new User();

        $user->name = $this->name;
        $user->lastName = $this->lastName;
        $user->nickName = $this->nickName;
        $user->email = $this->email;
        $user->defaultCurrencyCode = Currency::DEFAULT_CURRENCY_CODE;

        return $user;
    }
}
