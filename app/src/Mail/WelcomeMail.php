<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\EntityHeader;
use App\Service\Mailer\Mail;
use Spiral\Translator\Traits\TranslatorTrait;

class WelcomeMail extends BaseMail
{
    use TranslatorTrait;

    /**
     * @param \App\Database\EntityHeader<\App\Database\User> $userHeader
     */
    public function __construct(public EntityHeader $userHeader)
    {
        parent::__construct($userHeader);
    }

    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return parent::build()->subject($this->say('welcome_mail_subject'))
                              ->view('email/welcome');
    }
}
