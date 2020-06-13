<?php

declare(strict_types = 1);

namespace App\Request\Wallet;

use App\Database\Currency;
use Spiral\Filters\Filter;

class UpdateRequest extends Filter
{
    protected const SCHEMA = [
        'name'                => 'data:name',
        'defaultCurrencyCode' => 'data:defaultCurrencyCode',
    ];

    protected const VALIDATES = [
        'name'                => [
            'is_string',
            'type::notEmpty',
        ],
        'defaultCurrencyCode' => [
            'is_string',
            'type::notEmpty',
            ['entity::exists', Currency::class, 'code',],
        ],
    ];

    public function getName(): string
    {
        return (string) $this->getField('name');
    }

    public function getDefaultCurrencyCode(): string
    {
        return (string) $this->getField('defaultCurrencyCode');
    }
}