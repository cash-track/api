<?php

declare(strict_types = 1);

namespace App\Request\Wallet;

use App\Database\Currency;
use App\Database\Wallet;
use Spiral\Filters\Filter;

class CreateRequest extends Filter
{
    protected const SCHEMA = [
        'name'                => 'data:name',
        'slug'                => 'data:slug',
        'isPublic'            => 'data:isPublic',
        'defaultCurrencyCode' => 'data:defaultCurrencyCode',
    ];

    protected const VALIDATES = [
        'name'                => [
            'is_string',
            'type::notEmpty',
        ],
        'slug'                => [
            'is_string',
            ['string::regexp', '/^[a-zA-Z0-9\-_]*$/'],
            ['entity::unique', Wallet::class, 'slug'],
        ],
        'isPublic'            => [
            'type::boolean',
        ],
        'defaultCurrencyCode' => [
            'is_string',
            ['entity::exists', Currency::class, 'code', 'if' => ['withAll' => ['defaultCurrencyCode']]],
        ],
    ];

    /**
     * @return \App\Database\Wallet
     */
    public function createWallet(): Wallet
    {
        $wallet = new Wallet();

        $wallet->name                = $this->getField('name');
        $wallet->slug                = $this->getField('slug');
        $wallet->isPublic            = $this->getField('isPublic', false);
        $wallet->defaultCurrencyCode = $this->getField('defaultCurrencyCode');
        $wallet->totalAmount         = 0;

        return $wallet;
    }
}
