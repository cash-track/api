<?php

declare(strict_types=1);

namespace App\Mail;

use App\Service\Mailer\Mail;

final class TestMail extends BaseMail
{
    #[\Override]
    public function build(): Mail
    {
        return parent::build()->subject($this->say('test_mail_subject'))
                              ->view('email/test');
    }
}
