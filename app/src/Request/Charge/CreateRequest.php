<?php

declare(strict_types=1);

namespace App\Request\Charge;

use App\Database\Charge;
use App\Database\Tag;
use Spiral\Filters\Filter;

class CreateRequest extends Filter
{
    // TODO. Support custom currency with custom rate

    protected const SCHEMA = [
        'type'        => 'data:type',
        'amount'      => 'data:amount',
        'title'       => 'data:title',
        'description' => 'data:description',
        'tagIDs'      => 'data:tagIDs',
    ];

    protected const VALIDATES = [
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
        'tagIDs' => [
            ['array::of', ['entity:exists', Tag::class, 'id']],
        ],
    ];

    public function getType(): string
    {
        return (string) $this->getField('type', Charge::TYPE_EXPENSE);
    }

    public function getAmount(): float
    {
        return floatval($this->getField('amount', 0));
    }

    public function getTitle(): string
    {
        return (string) $this->getField('title');
    }

    public function getDescription(): string
    {
        return (string) $this->getField('description');
    }

    /**
     * @return array<array-key, int>
     * @throws \Spiral\Models\Exception\EntityExceptionInterface
     */
    public function getTagIDs(): array
    {
        return (array) $this->getField('tagIDs');
    }
}
