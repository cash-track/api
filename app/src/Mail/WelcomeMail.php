<?php

declare(strict_types=1);

namespace App\Mail;

use App\Service\Mailer\Mail;

class WelcomeMail extends BaseMail
{
    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return parent::build()->subject($this->say('welcome_mail_subject'))
                              ->view('email/welcome');
    }
}
