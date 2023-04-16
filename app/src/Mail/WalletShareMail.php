<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\User;
use App\Database\Wallet;
use App\Service\Mailer\Mail;
use Spiral\Translator\Traits\TranslatorTrait;

class WalletShareMail extends UserMail
{
    use TranslatorTrait;

    /**
     * @var \App\Database\User
     */
    public $sharer;

    /**
     * @var \App\Database\Wallet
     */
    public $wallet;

    /**
     * @var string
     */
    public $link;

    /**
     * WalletShareMail constructor.
     *
     * @param \App\Database\User $user
     * @param \App\Database\User $sharer
     * @param \App\Database\Wallet $wallet
     */
    public function __construct(User $user, User $sharer, Wallet $wallet, string $link)
    {
        parent::__construct($user);

        $this->sharer = $sharer;
        $this->wallet = $wallet;
        $this->link = $link;
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
