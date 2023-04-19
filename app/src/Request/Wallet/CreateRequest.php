<?php

declare(strict_types=1);

namespace App\Request\Wallet;

use App\Database\Currency;
use App\Database\Wallet;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class CreateRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $name = '';

    #[Data]
    public string $slug = '';

    #[Data]
    public bool $isPublic = false;

    #[Data]
    public string $defaultCurrencyCode = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'name' => [
                'is_string',
                'type::notEmpty',
            ],
            'slug' => [
                'is_string',
                ['string::regexp', '/^[a-zA-Z0-9\-_]*$/'],
                ['encrypted-entity::unique', Wallet::class, 'slug'],
            ],
            'isPublic' => [
                'type::boolean',
            ],
            'defaultCurrencyCode' => [
                'is_string',
                ['entity::exists', Currency::class, 'code', 'if' => ['withAll' => ['defaultCurrencyCode']]],
            ],
        ]);
    }

    public function createWallet(): Wallet
    {
        $wallet = new Wallet();

        $wallet->name = $this->name;
        $wallet->slug = $this->slug;
        $wallet->isPublic = $this->isPublic;
        $wallet->defaultCurrencyCode = $this->defaultCurrencyCode;
        $wallet->totalAmount = 0;

        if (! $wallet->slug) {
            $wallet->slug = str_slug($wallet->name);
        }

        if (! $wallet->defaultCurrencyCode) {
            $wallet->defaultCurrencyCode = Currency::DEFAULT_CURRENCY_CODE;
        }

        return $wallet;
    }
}
