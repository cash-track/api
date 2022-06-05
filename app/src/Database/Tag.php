<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\TagRepository;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Entity\Behavior;

#[ORM\Entity(repository: TagRepository::class)]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
#[ORM\Table\Index(columns: ['name', 'user_id'], unique: true)]
class Tag
{
    #[ORM\Column(type: 'primary')]
    public int|null $id = null;

    #[ORM\Column(type: 'string')]
    public string $name = '';

    #[ORM\Column(type: 'integer', name: 'user_id')]
    public int $userId = 0;

    #[ORM\Column(type: 'string(2)', nullable: true)]
    public string|null $icon = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public string|null $color = null;

    #[ORM\Relation\BelongsTo(target: User::class, innerKey: 'user_id', load: 'lazy')]
    private User $user;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    /**
     * @var \Cycle\ORM\Collection\Pivoted\PivotedCollection<int, \App\Database\Charge, \App\Database\TagCharge>
     */
    #[ORM\Relation\ManyToMany(target: Charge::class, through: TagCharge::class, collection: 'doctrine', load: 'lazy')]
    public PivotedCollection $charges;

    public function __construct()
    {
        $this->user = new User();
        $this->charges = new PivotedCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
}
