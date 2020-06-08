<?php

declare(strict_types = 1);

namespace App\Mail;

use App\Database\User;
use App\Service\Mailer\Mail;

class EmailConfirmationMail extends UserMail
{
    /**
     * @var string
     */
    public $link;

    /**
     * EmailConfirmationMail constructor.
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
        return parent::build()->subject('Confirm Your Account Email')
                              ->view('mail/confirm-email');
    }
}
