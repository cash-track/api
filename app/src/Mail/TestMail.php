<?php

declare(strict_types=1);

namespace App\Mail;

use App\Service\Mailer\Mail;

class TestMail extends UserMail
{
    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return parent::build()->subject('Test mail')
                              ->view('mail/test');
    }
}
