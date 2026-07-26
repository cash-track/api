<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\User;
use App\Exception\AuthenticationRequiredException;
use App\Exception\UnconfirmedProfileException;
use Spiral\Auth\AuthContextInterface;
use Spiral\Translator\Traits\TranslatorTrait;

abstract class AuthAwareController
{
    use TranslatorTrait;

    protected User $user;

    /**
     * Every route on a subclass must carry `group: 'auth'` — the constructor refuses to build
     * the controller otherwise, rather than leaving $user unset for the first read to fatal on.
     *
     * @throws AuthenticationRequiredException
     */
    public function __construct(AuthContextInterface $auth)
    {
        $user = $auth->getActor();

        if (! $user instanceof User) {
            throw new AuthenticationRequiredException($this->say('error_authentication_required'));
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
