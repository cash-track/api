<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\UserRepository;
use App\Security\PasswordContainerInterface;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Entity\Behavior;

#[ORM\Entity(repository: UserRepository::class)]
#[ORM\Table(indexes: [
    new ORM\Table\Index(columns: ['nick_name'], unique: true),
    new ORM\Table\Index(columns: ['email'], unique: true),
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class User implements PasswordContainerInterface
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Column('string')]
    public string $name = '';

    #[ORM\Column(type: 'string', name: 'last_name', nullable: true)]
    public string|null $lastName = null;

    #[ORM\Column(type: 'string', name: 'nick_name')]
    public string $nickName = '';

    #[ORM\Column('string')]
    public string $email = '';

    #[ORM\Column(type: 'boolean', name: 'is_email_confirmed', default: false)]
    public bool $isEmailConfirmed = false;

    #[ORM\Column(type: 'string(255)', name: 'photo', nullable: true)]
    public string|null $photo = null;

    #[ORM\Column(type: 'string(3)', name: 'default_currency_code')]
    public string|null $defaultCurrencyCode = null;

    #[ORM\Column('string')]
    public string $password = '';

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\Relation\BelongsTo(target: Currency::class, innerKey: 'default_currency_code', cascade: true, load: 'eager')]
    private Currency $defaultCurrency;

    #[ORM\Relation\ManyToMany(target: Wallet::class, through: UserWallet::class, collection: 'doctrine')]
    public PivotedCollection $wallets;

    public function __construct()
    {
        $this->defaultCurrency = new Currency();
        $this->wallets = new PivotedCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDefaultCurrency(): Currency
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency(Currency $currency): void
    {
        $this->defaultCurrency = $currency;
    }

    /**
     * {@inheritDoc}
     */
    public function getPasswordHash(): string
    {
        return $this->password;
    }

    /**
     * {@inheritDoc}
     */
    public function setPasswordHash(string $password): void
    {
        $this->password = $password;
    }

    public function fullName(): string
    {
        return "{$this->name} {$this->lastName}";
    }
}
