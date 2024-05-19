<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\LimitRepository;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Entity\Behavior;
use Cycle\ORM\Parser\Typecast;

#[ORM\Entity(repository: LimitRepository::class, typecast: [
    Typecast::class,
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class Limit
{
    const TYPE_INCOME  = '+';
    const TYPE_EXPENSE = '-';

    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Column(type: 'int', name: 'wallet_id')]
    public int $walletId = 0;

    #[ORM\Column(type: 'enum(+,-)', default: '+')]
    public string $type = '';

    #[ORM\Column('decimal(13,2)')]
    public float $amount = 0.0;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\Relation\BelongsTo(target: Wallet::class, innerKey: 'wallet_id')]
    private Wallet $wallet;

    /**
     * @var \Cycle\ORM\Collection\Pivoted\PivotedCollection<int, \App\Database\Tag, \App\Database\TagLimit>
     */
    #[ORM\Relation\ManyToMany(target: Tag::class, through: TagLimit::class, collection: 'doctrine')]
    public PivotedCollection $tags;

    public function __construct()
    {
        $this->wallet = new Wallet();
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
}
