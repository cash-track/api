<?php

declare(strict_types=1);

namespace App\Request;

use App\Database\ForgotPasswordRequest;
use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;
use Spiral\Filters\Model\FilterDefinitionInterface;
use Spiral\Filters\Model\HasFilterDefinition;
use Spiral\Validator\FilterDefinition;

final class ForgotPasswordResetRequest extends Filter implements HasFilterDefinition
{
    #[Data]
    public string $code = '';

    #[Data]
    public string $password = '';

    #[Data]
    public string $passwordConfirmation = '';

    #[\Override]
    public function filterDefinition(): FilterDefinitionInterface
    {
        return new FilterDefinition(validationRules: [
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
                ['match', 'password', 'error' => 'error_password_confirmation_not_match']
            ],
        ]);
    }
}
