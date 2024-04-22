<?php

declare(strict_types=1);

namespace App\Request\Profile;

use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class StorePasskeyRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $challenge = '';

    #[Data]
    public string $data = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'challenge' => ['type::notEmpty'],
            'data' => ['type::notEmpty'],
        ]);
    }
}
