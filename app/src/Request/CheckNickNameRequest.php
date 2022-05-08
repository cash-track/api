<?php

declare(strict_types=1);

namespace App\Request;

use App\Database\User;
use Spiral\Filters\Filter;

class CheckNickNameRequest extends Filter
{
    protected const SCHEMA = [
        'nickName' => 'data:nickName',
    ];

    protected const VALIDATES = [
        'nickName' => [
            'is_string',
            'type::notEmpty',
            ['string::longer', 3],
            ['string::regexp', '/^[a-zA-Z0-9_]*$/'],
            ['entity::unique', User::class, 'nickName', 'error' => 'Nick name already claimed'],
        ],
    ];
}
