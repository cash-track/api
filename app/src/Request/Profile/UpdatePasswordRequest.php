<?php

declare(strict_types=1);

namespace App\Request\Profile;

use App\Auth\AuthMiddleware;
use App\Database\User;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Attribute\Input\Header;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

class UpdatePasswordRequest extends Filter implements HasFilterDefinition
{
    #[Header(key: AuthMiddleware::HEADER_USER_ID)]
    public int $id = 0;

    #[Data]
    public string $currentPassword = '';

    #[Data]
    public string $newPassword = '';

    #[Data]
    public string $newPasswordConfirmation = '';

    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
            'currentPassword'         => [
                'type::notEmpty',
                ['password::verify', User::class],
            ],
            'newPassword'             => [
                'type::notEmpty',
                ['string::longer', 6],
            ],
            'newPasswordConfirmation' => [
                ['notEmpty', 'if' => ['withAll' => ['newPassword']]],
                ['match', 'newPassword', 'error' => 'New password confirmation does not match'],
            ],
        ]);
    }
}
