<?php

declare(strict_types=1);

namespace App\Request;

use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class ForgotPasswordCreateRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $email = '';

    #[\Override]
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'email' => [
                'address::email',
                'type::notEmpty',
            ],
        ]);
    }
}
