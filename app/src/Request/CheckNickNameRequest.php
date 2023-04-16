<?php

declare(strict_types=1);

namespace App\Request;

use App\Auth\AuthMiddleware;
use App\Database\User;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Attribute\Input\Header;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class CheckNickNameRequest extends Filter implements HasFilterDefinition
{
    #[Header(key: AuthMiddleware::HEADER_USER_ID)]
    public int $id = 0;

    #[Data]
    public string $nickName = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: self::validationRules());
    }

    public static function validationRules(): array
    {
        return [
            'nickName' => [
                'is_string',
                'type::notEmpty',
                ['string::longer', 3],
                ['string::regexp', '/^[a-zA-Z0-9_]*$/'],
                ['unique::verify', User::class, 'nickName', [], ['id'], 'error' => 'error_nick_name_claimed'],
            ],
        ];
    }
}
