<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Currency;
use App\Database\User;
use Cycle\ORM\ORM;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="userService")
 */
class UserService
{
    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    private $tr;

    /**
     * @var \Cycle\ORM\ORM
     */
    private $orm;

    /**
     * UserService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     */
    public function __construct(TransactionInterface $tr, ORM $orm)
    {
        $this->tr = $tr;
        $this->orm = $orm;
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
    public function create(string $name, string $email, string $password): User
    {
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->defaultCurrencyCode = Currency::DEFAULT_CURRENCY_CODE;

        $this->tr->persist($user);
        $this->tr->run();

        return $user;
    }

    /**
     * @param \App\Database\User $user
     * @param array $data
     * @return \App\Database\User
     * @throws \Throwable
     */
    public function update(User $user, array $data): User
    {
        if (count($data) == 0) {
            return $user;
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case User::FIELD_NAME:
                    $user->name = $value;
                    break;
                case User::FIELD_LAST_NAME:
                    $user->lastName = $value;
                    break;
                case User::FIELD_NICK_NAME:
                    $user->nickName = $value;
                    break;
                case User::FIELD_DEFAULT_CURRENCY_CODE:
                    if ($user->defaultCurrencyCode == $value) {
                        continue;
                    }
                    
                    $newCurrency = $this->orm->getRepository(Currency::class)->findByPK($value);

                    if ($newCurrency instanceof Currency) {
                        $user->defaultCurrency = $newCurrency;
                    }
                    
                    break;
            }
        }

        $this->tr->persist($user);
        $this->tr->run();

        return $user;
    }

    public function delete(User $user): void
    {
        $this->tr->delete($user);
        $this->tr->run();
    }
}
