<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\EntityHeader;
use App\Service\Mailer\Mail;
use Spiral\Translator\Traits\TranslatorTrait;

class ForgotPasswordMail extends BaseMail
{
    use TranslatorTrait;

    /**
     * @param \App\Database\EntityHeader<\App\Database\User> $userHeader
     * @param string $link
     */
    public function __construct(public EntityHeader $userHeader, public string $link)
    {
        parent::__construct($userHeader);
    }

    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return parent::build()->subject($this->say('forgot_password_mail_subject'))
                              ->view('email/forgot-password');
    }
}
