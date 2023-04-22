<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\User;
use App\Exception\UnconfirmedProfileException;
use Spiral\Auth\AuthScope;
use Spiral\Translator\Traits\TranslatorTrait;

abstract class AuthAwareController
{
    use TranslatorTrait;

    protected User $user;

    public function __construct(AuthScope $auth)
    {
        $user = $auth->getActor();

        if (! $user instanceof User) {
            return;
        }

        $this->user = $user;
    }

    protected function verifyIsProfileConfirmed(): void
    {
        if ($this->user->isEmailConfirmed) {
            return;
        }

        throw new UnconfirmedProfileException($this->say('error_profile_not_confirmed'));
    }
}
