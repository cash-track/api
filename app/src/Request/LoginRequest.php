<?php

declare(strict_types=1);

namespace App\Request;

use Spiral\Filters\Filter;

class LoginRequest extends Filter
{
    protected const SCHEMA = [
        'email' => 'data:email',
        'password' => 'data:password',
    ];

    protected const VALIDATES = [
        'email' => ['type::notEmpty'],
        'password' => ['type::notEmpty'],
    ];
}
