<?php

declare(strict_types=1);

namespace App\Request;

use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class RefreshTokenRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $refreshToken = '';

    #[\Override]
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'refreshToken' => [
                'is_string',
                'type::notEmpty',
                ['string::regexp', '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/'],
            ],
        ]);
    }
}
