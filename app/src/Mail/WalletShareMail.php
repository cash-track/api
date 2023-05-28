<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\EntityHeader;
use App\Database\User;
use App\Database\Wallet;
use App\Service\Mailer\Mail;
use Cycle\ORM\ORMInterface;
use Spiral\Translator\Traits\TranslatorTrait;

class WalletShareMail extends UserMail
{
    use TranslatorTrait;

    public ?User $sharer = null;

    public ?Wallet $wallet = null;

    /**
     * @param \App\Database\EntityHeader<\App\Database\User> $userHeader
     * @param \App\Database\EntityHeader<\App\Database\User> $sharerHeader
     * @param \App\Database\EntityHeader<\App\Database\Wallet> $walletHeader
     * @param string $link
     */
    public function __construct(
        public EntityHeader $userHeader,
        public EntityHeader $sharerHeader,
        public EntityHeader $walletHeader,
        public string $link
    ) {
        parent::__construct($userHeader);
    }

    public function hydrate(ORMInterface $orm)
    {
        parent::hydrate($orm);

        $this->sharer = $this->sharerHeader->hydrate($orm);
        $this->wallet = $this->walletHeader->hydrate($orm);
    }

    /**
     * {@inheritDoc}
     */
    public function build(): Mail
    {
        return parent::build()->subject($this->say('wallet_share_mail_subject'))
                              ->view('mail/wallet-share');
    }
}
