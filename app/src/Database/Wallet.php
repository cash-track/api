<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Typecast\EncryptedTypecast;
use App\Repository\WalletRepository;
use App\Service\Sort\Sortable;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Parser\Typecast;
use Doctrine\Common\Collections\ArrayCollection;
use Cycle\ORM\Entity\Behavior;

#[ORM\Entity(repository: WalletRepository::class, typecast: [
    Typecast::class,
    EncryptedTypecast::class,
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class Wallet implements Sortable
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Column(type: 'string(1536)', typecast: EncryptedTypecast::RULE)]
    public string $name = '';

    #[ORM\Column(type: 'string(1536)', typecast: EncryptedTypecast::RULE)]
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
    #[ORM\Relation\ManyToMany(target: User::class, through: UserWallet::class, collection: 'doctrine')]
    public PivotedCollection $users;

    #[ORM\Relation\HasMany(target: Limit::class, outerKey: 'wallet_id', load: 'lazy')]
    private PivotedCollection $limits;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection<int, \App\Database\Charge>|null
     */
    private ArrayCollection|null $latestCharges = null;

    public function __construct()
    {
        $this->defaultCurrency = new Currency();
        $this->users = new PivotedCollection();
        $this->limits = new PivotedCollection();
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
     * @return array<int, \App\Database\Limit>
     */
    public function getLimits(): array
    {
        $limits = [];

        foreach ($this->limits->getValues() as $limit) {
            if (! $limit instanceof Limit) {
                continue;
            }

            $limits[] = $limit;
        }

        return $limits;
    }

    /**
     * @return array<array-key, int>
     */
    public function getUserIDs(): array
    {
        $userIDs = [];

        foreach ($this->users->getValues() as $user) {
            if (! $user instanceof User || $user->id === null) {
                continue;
            }

            $userIDs[] = $user->id;
        }

        return $userIDs;
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

    /**
     * @return int|string
     */
    public function getSortIndex(): int|string
    {
        return $this->id ?? 0;
    }

    /**
     * @psalm-return \App\Database\EntityHeader<\App\Database\Wallet>
     * @return \App\Database\EntityHeader<\App\Database\Wallet>
     */
    public function getEntityHeader(): EntityHeader
    {
        /** @var \App\Database\EntityHeader<\App\Database\Wallet> $header */
        $header = new EntityHeader(self::class, ['id' => $this->id]);

        return $header;
    }
}
