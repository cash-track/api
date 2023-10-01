<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\EntityHeader;
use App\Database\User;
use App\Service\Mailer\Mail;
use Cycle\ORM\ORMInterface;

abstract class BaseMail extends Mail
{
    public ?User $user = null;

    /**
     * @param \App\Database\EntityHeader<\App\Database\User> $userHeader
     */
    public function __construct(public EntityHeader $userHeader)
    {
        parent::__construct();
    }

    public function hydrate(ORMInterface $orm)
    {
        $this->user = $this->userHeader->hydrate($orm);
    }

    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        if ($this->user === null) {
            throw new \RuntimeException('Empty user for user-required mail');
        }

        return $this->to($this->user->email, $this->user->fullName());
    }
}
