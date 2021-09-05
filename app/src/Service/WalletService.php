<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Currency;
use App\Database\User;
use App\Database\Wallet;
use App\Mail\WalletShareMail;
use App\Repository\CurrencyRepository;
use App\Service\Mailer\MailerInterface;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="walletService")
 */
class WalletService
{
    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    private $tr;

    /**
     * @var \App\Repository\CurrencyRepository
     */
    private $currencyRepository;

    /**
     * @var \App\Service\Mailer\MailerInterface
     */
    private $mailer;

    /**
     * @var \App\Service\UriService
     */
    private $uri;

    /**
     * WalletService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     * @param \App\Repository\CurrencyRepository $currencyRepository
     * @param \App\Service\UriService $uri
     * @param \App\Service\Mailer\MailerInterface $mailer
     */
    public function __construct(
        TransactionInterface $tr,
        CurrencyRepository $currencyRepository,
        UriService $uri,
        MailerInterface $mailer
    ) {
        $this->tr = $tr;
        $this->currencyRepository = $currencyRepository;
        $this->mailer = $mailer;
        $this->uri = $uri;
    }

    /**
     * Creates new Wallet and link to creator
     *
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\User $user
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function create(Wallet $wallet, User $user): Wallet
    {
        if (empty($wallet->slug)) {
            $this->setSlugByName($wallet);
        }

        $this->setDefaultCurrency($wallet, $user->defaultCurrencyCode);

        $wallet->users->add($user);

        return $this->store($wallet);
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function store(Wallet $wallet): Wallet
    {
        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @throws \Throwable
     */
    public function delete(Wallet $wallet): void
    {
        $this->tr->delete($wallet);
        $this->tr->run();
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function activate(Wallet $wallet): Wallet
    {
        if ($wallet->isActive) {
            return $wallet;
        }

        $wallet->isActive = true;

        return $this->store($wallet);
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function disable(Wallet $wallet): Wallet
    {
        if (! $wallet->isActive) {
            return $wallet;
        }

        $wallet->isActive = false;

        return $this->store($wallet);
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function archive(Wallet $wallet): Wallet
    {
        if ($wallet->isArchived) {
            return $wallet;
        }

        $wallet->isArchived = true;

        return $this->store($wallet);
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function unArchive(Wallet $wallet): Wallet
    {
        if (! $wallet->isArchived) {
            return $wallet;
        }

        $wallet->isArchived = false;

        return $this->store($wallet);
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\User $user
     * @param \App\Database\User $sharer
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function share(Wallet $wallet, User $user, User $sharer): Wallet
    {
        if ($wallet->users->contains($user)) {
            return $wallet;
        }

        $wallet->users->add($user);
        $wallet = $this->store($wallet);

        $this->mailer->send(new WalletShareMail($user, $sharer, $wallet, $this->uri->wallet($wallet)));

        return $wallet;
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\User $user
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function revoke(Wallet $wallet, User $user): Wallet
    {
        if (! $wallet->users->contains($user)) {
            return $wallet;
        }

        $wallet->users->removeElement($user);

        return $this->store($wallet);
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @return \App\Database\Wallet
     * @throws \Exception
     */
    protected function setSlugByName(Wallet $wallet): Wallet
    {
        $wallet->slug = str_slug($wallet->name . ' ' . bin2hex(random_bytes(3)));

        return $wallet;
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param string|null $defaultCurrencyCode
     * @return \App\Database\Wallet
     */
    protected function setDefaultCurrency(Wallet $wallet, ?string $defaultCurrencyCode): Wallet
    {
        $code = $defaultCurrencyCode ?? Currency::DEFAULT_CURRENCY_CODE;

        if (!empty($wallet->defaultCurrencyCode)) {
            $code = $wallet->defaultCurrencyCode;
        }

        $currency = $this->currencyRepository->findByPK($code);

        if (! $currency instanceof Currency) {
            throw new \RuntimeException("Unable to get currency by code [{$code}]");
        }

        $wallet->defaultCurrency = $currency;

        return $wallet;
    }
}
