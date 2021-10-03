<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Cycle\Entity(repository = "App\Repository\WalletRepository", mapper = "App\Mapper\TimestampedMapper")
 */
class Wallet
{
    /**
     * @Cycle\Column(type = "primary")
     * @var int
     */
    public $id = 0;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $name = '';

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $slug = '';

    /**
     * @Cycle\Column(type = "decimal(13,2)", default = "0", name = "total_amount")
     * @var float
     */
    public $totalAmount = 0.0;

    /**
     * @Cycle\Column(type = "boolean", default = "1", name = "is_active")
     * @var bool
     */
    public $isActive = true;

    /**
     * @Cycle\Column(type = "boolean", default = "0", name = "is_archived")
     * @var bool
     */
    public $isArchived = false;

    /**
     * @Cycle\Column(type = "boolean", default = "0", name = "is_public")
     * @var bool
     */
    public $isPublic = false;

    /**
     * @Cycle\Column(type = "string(3)", name = "default_currency_code")
     * @var string|null
     */
    public $defaultCurrencyCode;

    /**
     * @Cycle\Column(type = "datetime", name="created_at")
     * @var \DateTimeImmutable
     */
    public $createdAt;

    /**
     * @Cycle\Column(type = "datetime", name="updated_at")
     * @var \DateTimeImmutable
     */
    public $updatedAt;

    /**
     * @Cycle\Relation\BelongsTo(target = "App\Database\Currency", innerKey = "default_currency_code", cascade = false, load = "eager")
     * @var \App\Database\Currency
     */
    public $defaultCurrency;

    /**
     * @Cycle\Relation\HasMany(target = "App\Database\Charge")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $charges;

    /**
     * @Cycle\Relation\ManyToMany(target = "App\Database\User", though = "App\Database\UserWallet")
     * @var \Cycle\ORM\Relation\Pivoted\PivotedCollection
     */
    public $users;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public ArrayCollection $latestCharges;

    /**
     * Wallet constructor.
     */
    public function __construct()
    {
        $this->defaultCurrency = new Currency();
        $this->charges = new ArrayCollection();
        $this->users = new PivotedCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->latestCharges = new ArrayCollection();
    }
}
