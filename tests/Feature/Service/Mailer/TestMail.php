<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Mailer;

use App\Service\Mailer\Mail;

class TestMail extends Mail
{
    public string $email = '';

    public string $fullName = '';

    protected string $token = '';

    public function setToken(string $token): Mail
    {
        $this->token = $token;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return $this->to($this->email, $this->fullName);
    }
}
