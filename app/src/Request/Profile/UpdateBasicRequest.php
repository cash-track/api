<?php

declare(strict_types=1);

namespace App\Request\Profile;

use App\Database\Currency;
use App\Database\User;
use Spiral\Filters\Filter;

class UpdateBasicRequest extends Filter
{
    protected const SCHEMA = [
        'name' => 'data:name',
        'lastName' => 'data:lastName',
        'nickName' => 'data:nickName',
        'defaultCurrencyCode' => 'data:defaultCurrencyCode'
    ];

    protected const VALIDATES = [
        'name' => [
            'is_string',
            'type::notEmpty',
        ],
        'lastName' => [
            'is_string',
        ],
        'nickName' => [
            'is_string',
            'type::notEmpty',
            ['string::longer', 3],
            ['string::regexp', '/^[a-zA-Z0-9_]*$/'],
            ['entity::unique', User::class, 'nickName'],
        ],
        'defaultCurrencyCode' => [
            'is_string',
            'type::notEmpty',
            ['entity::exists', Currency::class, 'code'],
        ],
    ];

    public function getName(): string
    {
        return $this->getField('name');
    }

    public function getLastName(): string
    {
        return $this->getField('lastName');
    }

    public function getNickName(): string
    {
        return $this->getField('nickName');
    }

    public function getDefaultCurrencyCode(): string
    {
        return $this->getField('defaultCurrencyCode');
    }
}
