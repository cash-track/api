<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Currency;
use App\Database\User;
use App\Database\Wallet;
use App\Mail\WalletShareMail;
use App\Repository\CurrencyRepository;
use App\Service\Mailer\MailerInterface;
use Cycle\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class WalletService
{
    public function __construct(
        private readonly EntityManagerInterface $tr,
        private readonly CurrencyRepository $currencyRepository,
        private readonly UriService $uri,
        private readonly MailerInterface $mailer,
        private readonly SluggerInterface $slugger,
    ) {
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

    public function store(Wallet $wallet): Wallet
    {
        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function delete(Wallet $wallet): void
    {
        $this->tr->delete($wallet);
        $this->tr->run();
    }

    public function activate(Wallet $wallet): Wallet
    {
        if ($wallet->isActive) {
            return $wallet;
        }

        $wallet->isActive = true;

        return $this->store($wallet);
    }

    public function disable(Wallet $wallet): Wallet
    {
        if (! $wallet->isActive) {
            return $wallet;
        }

        $wallet->isActive = false;

        return $this->store($wallet);
    }

    public function archive(Wallet $wallet): Wallet
    {
        if ($wallet->isArchived) {
            return $wallet;
        }

        $wallet->isArchived = true;

        return $this->store($wallet);
    }

    public function unArchive(Wallet $wallet): Wallet
    {
        if (! $wallet->isArchived) {
            return $wallet;
        }

        $wallet->isArchived = false;

        return $this->store($wallet);
    }

    public function share(Wallet $wallet, User $user, User $sharer): Wallet
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($wallet->users->contains($user) === true) {
            return $wallet;
        }

        $wallet->users->add($user);
        $wallet = $this->store($wallet);

        $this->mailer->send(new WalletShareMail(
            $user->getEntityHeader(),
            $sharer->getEntityHeader(),
            $wallet->getEntityHeader(),
            $this->uri->wallet($wallet),
        ));

        return $wallet;
    }

    public function revoke(Wallet $wallet, User $user): Wallet
    {
        /** @psalm-suppress InvalidArgument */
        if ($wallet->users->contains($user) === false) {
            return $wallet;
        }

        $wallet->users->removeElement($user);

        return $this->store($wallet);
    }

    protected function setSlugByName(Wallet $wallet): Wallet
    {
        $wallet->slug = $this->slugger->slug($wallet->name . ' ' . bin2hex(random_bytes(3)))->lower()->toString();

        return $wallet;
    }

    protected function setDefaultCurrency(Wallet $wallet, ?string $defaultCurrencyCode): Wallet
    {
        $code = $defaultCurrencyCode ?? Currency::DEFAULT_CURRENCY_CODE;

        if ($wallet->defaultCurrencyCode !== null && $wallet->defaultCurrencyCode !== '') {
            $code = $wallet->defaultCurrencyCode;
        }

        /** @var \App\Database\Currency|null $currency */
        $currency = $this->currencyRepository->findByPK($code);

        if (! $currency instanceof Currency) {
            throw new \RuntimeException("Unable to get currency by code [{$code}]");
        }

        $wallet->setDefaultCurrency($currency);

        return $wallet;
    }
}
