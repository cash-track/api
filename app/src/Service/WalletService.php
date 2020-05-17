<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Currency;
use App\Database\User;
use App\Database\Wallet;
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
     * WalletService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     */
    public function __construct(TransactionInterface $tr)
    {
        $this->tr = $tr;
    }

    /**
     * Creates new Wallet and link to creator
     *
     * @param \App\Database\User $user
     * @param string $name
     * @param bool $isPublic
     * @param \App\Database\Currency|null $defaultCurrency
     * @return \App\Database\Wallet
     * @throws \Throwable
     */
    public function create(User $user, string $name, bool $isPublic = false, Currency $defaultCurrency = null): Wallet
    {
        $wallet = new Wallet();
        $wallet->name = $name;
        $wallet->slug = str_slug($name);
        $wallet->isPublic = $isPublic;
        $wallet->users->add($user);

        if ($defaultCurrency instanceof Currency) {
            $wallet->defaultCurrency = $defaultCurrency;
        } else {
            $wallet->defaultCurrency = $user->defaultCurrency;
        }

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        if (count($data) == 0) {
            return $wallet;
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'name':
                    $wallet->name = $value;
                    break;
                case 'slug':
                    $wallet->slug = $value;
                    break;
            }
        }

        $this->tr->persist($wallet);
        $this->tr->run();

        return $wallet;
    }

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
}
