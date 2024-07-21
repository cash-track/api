<?php

declare(strict_types=1);

namespace App\Request\Limit;

use App\Database\Limit;
use App\Database\Tag;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Attribute\Setter;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class CreateRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $type = '';

    #[Data]
    #[Setter(filter: 'floatval')]
    public float $amount = 0.0;

    /**
     * @var array<array-key, int>
     */
    #[Data]
    public array $tags = [];

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'type'        => [
                'type::notEmpty',
                ['in_array', [Limit::TYPE_EXPENSE, Limit::TYPE_INCOME], true],
            ],
            'amount'      => [
                'is_numeric',
                'type::notEmpty',
                ['number::higher', 0]
            ],
            'tags' => [
                'type::notEmpty',
                ['array::longer', 0],
                ['array::of', ['entity:exists', Tag::class, 'id']],
            ],
        ]);
    }
}
