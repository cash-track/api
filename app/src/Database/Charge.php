<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\ChargeRepository;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Entity\Behavior;
use Cycle\ORM\Parser\Typecast;
use Ramsey\Uuid\UuidInterface;

/** @psalm-suppress InvalidArgument */
#[ORM\Entity(repository: ChargeRepository::class, typecast: [
    Typecast::class,
])]
#[Behavior\Uuid\Uuid4(field: 'id', column: 'id')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
final class Charge
{
    const string TYPE_INCOME  = '+';
    const string TYPE_EXPENSE = '-';

    #[ORM\Column(type: 'uuid', primary: true, name: 'id')]
    public UuidInterface|null $id = null;

    #[ORM\Column(type: 'int', name: 'wallet_id')]
    public int $walletId = 0;

    #[ORM\Column(type: 'int', name: 'user_id')]
    public int $userId = 0;

    #[ORM\Column(type: 'enum(+,-)', default: '+')]
    public string $type = '';

    #[ORM\Column('decimal(13,2)')]
    public float $amount = 0.0;

    #[ORM\Column(type: 'string')]
    public string $title = '';

    #[ORM\Column(type: 'integer', name: 'currency_exchange_id', nullable: true)]
    public int|null $currencyExchangeId = null;

    #[ORM\Column(type: 'text')]
    public string $description = '';

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\Relation\BelongsTo(target: Wallet::class, innerKey: 'wallet_id')]
    private Wallet $wallet;

    #[ORM\Relation\BelongsTo(target: User::class, innerKey: 'user_id')]
    private User $user;

    #[ORM\Relation\BelongsTo(target: CurrencyExchange::class, innerKey: 'currency_exchange_id', nullable: true, fkAction: 'SET NULL')]
    private CurrencyExchange|null $currencyExchange = null;

    /**
     * @var \Cycle\ORM\Collection\Pivoted\PivotedCollection<int, \App\Database\Tag, \App\Database\TagCharge>
     */
    #[ORM\Relation\ManyToMany(target: Tag::class, through: TagCharge::class, collection: 'doctrine')]
    public PivotedCollection $tags;

    public function __construct()
    {
        $this->wallet = new Wallet();
        $this->user = new User();
        $this->tags = new PivotedCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function setWallet(Wallet $wallet): void
    {
        $this->wallet = $wallet;
        $this->walletId = (int) $wallet->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->userId = (int) $user->id;
    }

    /**
     * @return array<int, \App\Database\Tag>
     */
    public function getTags(): array
    {
        $tags = [];

        foreach ($this->tags->getValues() as $tag) {
            if (! $tag instanceof Tag) {
                continue;
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    public function getCurrencyExchange(): ?CurrencyExchange
    {
        return $this->currencyExchange;
    }

    public function setCurrencyExchange(CurrencyExchange|null $exchange): void
    {
        $this->currencyExchange = $exchange;
        $this->currencyExchangeId = $exchange !== null ? $exchange->id : null;
    }
}
