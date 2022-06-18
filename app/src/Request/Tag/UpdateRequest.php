<?php

declare(strict_types=1);

namespace App\Request\Tag;

use App\Database\Tag;
use Spiral\Filters\Filter;

class UpdateRequest extends Filter
{
    protected const SCHEMA = [
        'name'    => 'data:name',
        'icon'    => 'data:icon',
        'color'   => 'data:color',
        'user_id' => 'data:user_id',
    ];

    protected const VALIDATES = [
        'name' => [
            ['is_string'],
            ['string::range', 3, 255],
            ['type::notEmpty'],
            ['string::regexp', '/^[^\s]*$/'],
            ['unique::verify', Tag::class, 'name', ['user_id'], ['id']],
        ],
        'icon' => [
            ['is_string'],
            ['string::range', 1, 7]
        ],
        'color' => [
            ['is_string'],
            ['string::regexp', '/^\#[0-9a-fA-F]{6}$/'],
        ],
    ];

    public function getName(): string
    {
        return (string) $this->getField('name');
    }

    public function getIcon(): ?string
    {
        $value = $this->getField('icon');

        return is_string($value) ? $value : null;
    }

    public function getColor(): ?string
    {
        $value = $this->getField('color');

        return is_string($value) ? $value : null;
    }
}
