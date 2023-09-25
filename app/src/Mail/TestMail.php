<?php

declare(strict_types=1);

namespace App\Mail;

use App\Service\Mailer\Mail;
use Spiral\Translator\Traits\TranslatorTrait;

class TestMail extends UserMail
{
    use TranslatorTrait;

    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return parent::build()->subject($this->say('test_mail_subject'))
                              ->view('email/test');
    }
}
