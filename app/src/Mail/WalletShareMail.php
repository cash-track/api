<?php

declare(strict_types=1);

namespace App\Mail;

use App\Database\EntityHeader;
use App\Service\Mailer\Mail;
use Spiral\Translator\Traits\TranslatorTrait;

class WalletShareMail extends UserMail
{
    use TranslatorTrait;

    public function __construct(
        public EntityHeader $userHeader,
        public EntityHeader $sharerHeader,
        public EntityHeader $walletHeader,
        public string $link
    ) {
        parent::__construct($userHeader);
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
