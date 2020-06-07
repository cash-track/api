<?php

declare(strict_types = 1);

namespace App\Mail;

use App\Database\User;
use App\Service\Mailer\Mail;

class TestMail extends Mail
{
    /**
     * @var \App\Database\User
     */
    public $user;

    /**
     * TestMail constructor.
     *
     * @param \App\Database\User $user
     */
    public function __construct(User $user)
    {
        parent::__construct();

        $this->user = $user;
    }

    /**
     * {@inheritDoc}
     *
     * @return \App\Service\Mailer\Mail
     */
    public function build(): Mail
    {
        return $this->subject('Test mail')
                    ->from('test@cash-track.ml', 'Support Manager')
                    ->to($this->user->email, $this->user->fullName())
                    ->view('mail/test');
    }
}
