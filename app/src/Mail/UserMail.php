<?php

declare(strict_types = 1);

namespace App\Mail;

use App\Database\User;
use App\Service\Mailer\Mail as Message;

class UserMail extends Mail
{
    /**
     * @var \App\Database\User
     */
    public $user;

    /**
     * UserMail constructor.
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
     */
    public function build(): Message
    {
        return parent::build()->to($this->user->email, $this->user->fullName());
    }
}
