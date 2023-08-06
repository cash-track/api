<?php

declare(strict_types=1);

namespace App\Request;

use App\Database\Currency;
use App\Database\User;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Translator\Translator;
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

    #[Data]
    public string $locale = '';

    public function __construct(
        private readonly Translator $translator,
    ) {
    }

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
                ['encrypted-entity::unique', User::class, 'nickName'],
            ],
            'email' => [
                'address::email',
                'type::notEmpty',
                ['encrypted-entity::unique', User::class, 'email'],
            ],
            'password' => [
                'type::notEmpty',
                ['string::longer', 6],
            ],
            'passwordConfirmation' => [
                ['notEmpty', 'if' => ['withAll' => ['password']]],
                ['match', 'password', 'error' => 'error_password_confirmation_not_match']
            ],
            'locale' => [
                'is_string',
                ['in_array', $this->translator->getCatalogueManager()->getLocales(), true],
            ]
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
        $user->password = $this->password;

        return $user;
    }
}
