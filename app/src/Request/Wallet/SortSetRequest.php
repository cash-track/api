<?php

declare(strict_types=1);

namespace App\Request\Wallet;

use App\Database\Wallet;
use Spiral\Filters\Filter;

class SortSetRequest extends Filter
{
    protected const SCHEMA = [
        'sort' => 'data:sort',
    ];

    protected const VALIDATES = [
        'sort' => [
            'notEmpty',
            [
                'arrayOf', ['entity:exists', Wallet::class],
                'error' => 'One or more wallets does not exists',
            ],
        ],
    ];

    /**
     * @throws \Spiral\Models\Exception\EntityExceptionInterface
     */
    public function getSort(): array
    {
        return (array) $this->getField('sort');
    }
}
