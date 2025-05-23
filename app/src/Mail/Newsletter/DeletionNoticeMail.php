<?php

declare(strict_types=1);

namespace App\Mail\Newsletter;

use App\Mail\BaseMail;
use App\Service\Mailer\Mail;

final class DeletionNoticeMail extends BaseMail
{
    #[\Override]
    public function build(): Mail
    {
        return parent::build()->subject($this->say('deletion_notice_nl_mail_subject'))
                              ->view('email/deletion-notice-newsletter');
    }
}
