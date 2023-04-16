<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\User;
use App\Service\Mailer\Mail;
use Spiral\Translator\Traits\TranslatorTrait;

class ForgotPasswordMail extends UserMail
{
    use TranslatorTrait;

    /**
     * @var string
     */
    public $link;

    /**
     * ForgotPasswordMail constructor.
     *
     * @param \App\Database\User $user
     * @param string $link
     */
    public function __construct(User $user, string $link)
    {
        parent::__construct($user);

        $this->link = $link;
    }

    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return parent::build()->subject($this->say('forgot_password_mail_subject'))
                              ->view('mail/forgot-password');
    }
}
