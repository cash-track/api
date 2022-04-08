<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\WalletRepository;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Cycle\ORM\Entity\Behavior;

#[ORM\Entity(repository: WalletRepository::class)]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class Wallet
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Column('string')]
    public string $name = '';

    #[ORM\Column('string')]
    public string $slug = '';

    #[ORM\Column(type: 'decimal(13,2)', name: 'total_amount', default: 0.0)]
    public float $totalAmount = 0.0;

    #[ORM\Column(type: 'boolean', name: 'is_active', default: true)]
    public bool $isActive = true;

    #[ORM\Column(type: 'boolean', name: 'is_archived', default: false)]
    public bool $isArchived = false;

    #[ORM\Column(type: 'boolean', name: 'is_public', default: false)]
    public bool $isPublic = false;

    #[ORM\Column(type: 'string(3)', name: 'default_currency_code')]
    public string|null $defaultCurrencyCode = null;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\Relation\BelongsTo(target: Currency::class, innerKey: 'default_currency_code', cascade: true, load: 'eager')]
    private Currency $defaultCurrency;

    /**
     * @var \Cycle\ORM\Collection\Pivoted\PivotedCollection<int, \App\Database\User, \App\Database\UserWallet>
     */
    #[ORM\Relation\ManyToMany(target: User::class, through: UserWallet::class)]
    public PivotedCollection $users;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection<int, \App\Database\Charge>|null
     */
    private ArrayCollection|null $latestCharges = null;

    public function __construct()
    {
        $this->defaultCurrency = new Currency();
        $this->users = new PivotedCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDefaultCurrency(): Currency
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency(Currency $defaultCurrency): void
    {
        $this->defaultCurrency = $defaultCurrency;
    }

    /**
     * @return array<int, \App\Database\User>
     */
    public function getUsers(): array
    {
        $users = [];

        foreach ($this->users->getValues() as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection<int, \App\Database\Charge>|null
     */
    public function getLatestCharges(): ?ArrayCollection
    {
        return $this->latestCharges;
    }

    /**
     * @param array<int, \App\Database\Charge> $latestCharges
     */
    public function setLatestCharges(array $latestCharges): void
    {
        $this->latestCharges = new ArrayCollection($latestCharges);
    }
}
