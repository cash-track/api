<?php

declare(strict_types=1);

namespace App\Request\Charge;

use App\Database\Charge;
use App\Database\Tag;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Attribute\Setter;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class CreateRequest extends Filter implements HasFilterDefinition
{
    const string DATE_FORMAT = 'Y-m-d H:i:s';

    // TODO. Support custom currency with custom rate

    #[Data]
    public string $type = '';

    #[Data]
    #[Setter(filter: 'floatval')]
    public float $amount = 0.0;

    #[Data]
    public string $title = '';

    #[Data]
    public string $description = '';

    /**
     * @var array<array-key, int>
     */
    #[Data]
    public array $tags = [];

    #[Data]
    public string $dateTime = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'type'        => [
                'type::notEmpty',
                ['in_array', [Charge::TYPE_EXPENSE, Charge::TYPE_INCOME], true],
            ],
            'amount'      => [
                'is_numeric',
                'type::notEmpty',
                ['number::higher', 0]
            ],
            'title'       => [
                'is_string',
                'type::notEmpty',
            ],
            'description' => [
                'is_string',
            ],
            'tags' => [
                ['array::of', ['entity:exists', Tag::class, 'id']],
            ],
            'dateTime' => [
                'is_string',
                ['datetime:format', self::DATE_FORMAT],
                ['datetime:past', true],
            ],
        ]);
    }

    public function getDateTime(): ?\DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $this->dateTime) ?: null;
    }
}
