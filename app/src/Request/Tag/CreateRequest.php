<?php

declare(strict_types=1);

namespace App\Request\Tag;

use App\Database\Tag;
use Spiral\Filters\Filter;

class CreateRequest extends Filter
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
            ['string::regexp', '/^[a-zA-Z0-9\-_]*$/'],
            ['entity::unique', Tag::class, 'name', ['user_id']],
        ],
        'icon' => [
            ['is_string'],
            ['string::length', 2]
        ],
        'color' => [
            ['is_string'],
            ['string::regexp', '/^\#[0-9a-fA-F]{6}$/'],
        ],
    ];

    public function createTag(): Tag
    {
        $tag = new Tag();

        $tag->name = $this->getField('name');
        $tag->icon = $this->getField('icon');
        $tag->color = $this->getField('color');

        return $tag;
    }
}
