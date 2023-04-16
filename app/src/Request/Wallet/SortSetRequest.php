<?php

declare(strict_types=1);

namespace App\Request\Wallet;

use App\Database\Wallet;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class SortSetRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public array $sort = [];

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'sort' => [
                'notEmpty',
                [
                    'arrayOf', ['entity:exists', Wallet::class],
                    'error' => 'error_wallet_does_not_exists',
                ],
            ],
        ]);
    }
}
