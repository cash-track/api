<?php

declare(strict_types = 1);

namespace App\Mail;

use App\Service\Mailer\Mail as Message;

abstract class Mail extends Message
{
    const DEFAULT_FROM_ADDRESS = 'support@cash-track.ml';
    const DEFAULT_FROM_NAME = 'Support Manager';

    /**
     * {@inheritDoc}
     */
    public function build(): Message
    {
        return $this->from(self::DEFAULT_FROM_ADDRESS, self::DEFAULT_FROM_NAME);
    }
}
