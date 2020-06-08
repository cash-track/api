<?php

declare(strict_types=1);

namespace App\Request;

use App\Database\User;
use Spiral\Filters\Filter;

class ForgotPasswordCreateRequest extends Filter
{
    protected const SCHEMA = [
        'email' => 'data:email',
    ];

    protected const VALIDATES = [
        'email' => [
            'address::email',
            'type::notEmpty',
            ['entity::exists', User::class, 'email'],
        ],
    ];

    public function getEmail(): string
    {
        return (string) $this->getField('email');
    }
}
