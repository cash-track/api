<?php

declare(strict_types = 1);

namespace App\Request\Profile;

use Spiral\Filters\Filter;

class UpdatePasswordRequest extends Filter
{
    protected const SCHEMA = [
        'currentPassword'      => 'data:currentPassword',
        'newPassword'             => 'data:newPassword',
        'newPasswordConfirmation' => 'data:newPasswordConfirmation',
    ];

    protected const VALIDATES = [
        'currentPassword'      => [
            'type::notEmpty',
            'password::verify',
        ],
        'newPassword'             => [
            'type::notEmpty',
            ['string::longer', 6],
        ],
        'newPasswordConfirmation' => [
            ['notEmpty', 'if' => ['withAll' => ['newPassword']]],
            ['match', 'newPassword', 'error' => 'New password confirmation does not match'],
        ],
    ];

    public function getNewPassword(): string
    {
        return $this->getField('newPassword');
    }
}
