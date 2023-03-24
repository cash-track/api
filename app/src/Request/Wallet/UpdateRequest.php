<?php

declare(strict_types=1);

namespace App\Request\Wallet;

use App\Database\Currency;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class UpdateRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $name = '';

    #[Data]
    public bool $isPublic = false;

    #[Data]
    public string $defaultCurrencyCode = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'name' => [
                'is_string',
                'type::notEmpty',
            ],
            'isPublic' => [
                'type::boolean',
            ],
            'defaultCurrencyCode' => [
                'is_string',
                'type::notEmpty',
                ['entity::exists', Currency::class, 'code',],
            ],
        ]);
    }
}
