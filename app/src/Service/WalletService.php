<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\User;
use App\Database\Wallet;
use App\Repository\CurrencyRepository;
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
     * WalletService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     * @param \App\Repository\CurrencyRepository $currencyRepository
     */
    public function __construct(TransactionInterface $tr, CurrencyRepository $currencyRepository)
    {
        $this->tr = $tr;
        $this->currencyRepository = $currencyRepository;
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

    public function share(Wallet $wallet, User $user): Wallet
    {
        if ($wallet->users->contains($user)) {
            return $wallet;
        }

        $wallet->users->add($user);

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function revoke(Wallet $wallet, User $user): Wallet
    {
        if (!$wallet->users->contains($user)) {
            return $wallet;
        }

        $wallet->users->removeElement($user);

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function archive(Wallet $wallet): Wallet
    {
        $wallet->isArchived = true;

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function unArchive(Wallet $wallet): Wallet
    {
        $wallet->isArchived = false;

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function publish(Wallet $wallet): Wallet
    {
        $wallet->isPublic = true;

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function private(Wallet $wallet): Wallet
    {
        $wallet->isPublic = false;

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function activate(Wallet $wallet): Wallet
    {
        $wallet->isActive = true;

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function disable(Wallet $wallet): Wallet
    {
        $wallet->isActive = false;

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
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
     * @param string $defaultCurrencyCode
     * @return \App\Database\Wallet
     */
    protected function setDefaultCurrency(Wallet $wallet, string $defaultCurrencyCode): Wallet
    {
        $code = $defaultCurrencyCode;

        if (!empty($wallet->defaultCurrencyCode)) {
            $code = $wallet->defaultCurrencyCode;
        }

        $wallet->defaultCurrency = $this->currencyRepository->findByPK($code);

        return $wallet;
    }
}
