<?php

declare(strict_types=1);

namespace App\Request\Charge;

use App\Database\Charge;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

final class MoveRequest extends Filter implements HasFilterDefinition
{
    /**
     * @var array<array-key, string>
     */
    #[Data]
    public array $chargeIds = [];

    #[\Override]
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'chargeIds' => [
                ['array::of', ['entity:exists', Charge::class]],
            ],
        ]);
    }
}
