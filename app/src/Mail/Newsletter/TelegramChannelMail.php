<?php

declare(strict_types=1);

namespace App\Mail\Newsletter;

use App\Mail\BaseMail;
use App\Service\Mailer\Mail;

final class TelegramChannelMail extends BaseMail
{
    #[\Override]
    public function build(): Mail
    {
        return parent::build()->subject($this->say('telegram_channel_nl_mail_subject'))
                              ->view('email/telegram-channel-newsletter');
    }
}
