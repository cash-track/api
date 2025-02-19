<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\EntityHeader;
use App\Database\User;
use App\Service\Mailer\Mail;
use Cycle\ORM\ORMInterface;
use Spiral\Translator\Traits\TranslatorTrait;

abstract class BaseMail extends Mail
{
    use TranslatorTrait;

    public ?User $user = null;

    /**
     * @param \App\Database\EntityHeader<\App\Database\User> $userHeader
     */
    public function __construct(public EntityHeader $userHeader)
    {
        parent::__construct();
    }

    #[\Override]
    public function hydrate(ORMInterface $orm): void
    {
        $this->user = $this->userHeader->hydrate($orm);
    }

    #[\Override]
    public function build(): Mail
    {
        if ($this->user === null) {
            throw new \RuntimeException('Empty user for user-required mail');
        }

        return $this->to($this->user->email, $this->user->fullName());
    }
}
