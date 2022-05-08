<?php

declare(strict_types=1);

namespace App\Request;

use App\Database\ForgotPasswordRequest;
use Spiral\Filters\Filter;

class ForgotPasswordResetRequest extends Filter
{
    protected const SCHEMA = [
        'code' => 'data:code',
        'password' => 'data:password',
        'passwordConfirmation' => 'data:passwordConfirmation',
    ];

    protected const VALIDATES = [
        'code' => [
            'type::notEmpty',
            ['entity::exists', ForgotPasswordRequest::class, 'code'],
        ],
        'password' => [
            'type::notEmpty',
            ['string::longer', 6],
        ],
        'passwordConfirmation' => [
            ['notEmpty', 'if' => ['withAll' => ['password']]],
            ['match', 'password', 'error' => 'Password confirmation does not match']
        ],
    ];

    public function getCode(): string
    {
        return $this->getField('code');
    }

    public function getPassword(): string
    {
        return $this->getField('password');
    }
}
