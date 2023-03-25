<?php

declare(strict_types=1);

namespace App\Request\Profile;

use App\Auth\AuthMiddleware;
use App\Database\Currency;
use App\Database\User;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Attribute\Input\Header;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class UpdateBasicRequest extends Filter implements HasFilterDefinition
{
    #[Header(key: AuthMiddleware::HEADER_USER_ID)]
    public int $id = 0;

    #[Data]
    public string $name = '';

    #[Data]
    public string $lastName = '';

    #[Data]
    public string $nickName = '';

    #[Data]
    public string $defaultCurrencyCode = '';

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
                ['unique::verify', User::class, 'nickName', [], ['id']],
            ],
            'defaultCurrencyCode' => [
                'is_string',
                'type::notEmpty',
                ['entity::exists', Currency::class, 'code'],
            ],
        ]);
    }
}
