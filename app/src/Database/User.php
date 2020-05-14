<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Cycle\Entity(repository = "App\Repository\UserRepository", mapper = "App\Mapper\TimestampedMapper")
 */
class User
{
    /**
     * @Cycle\Column(type = "primary")
     * @var int
     */
    public $id;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $name;

    /**
     * @Cycle\Column(type = "string", nullable = true, name = "last_name")
     * @var string
     */
    public $lastName;

    /**
     * @Cycle\Column(type = "string", name = "nick_name")
     * @var string
     */
    public $nickName;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $email;

    /**
     * @Cycle\Column(type = "string(255)", nullable = true, name = "photo_url")
     * @var string|null
     */
    public $photoUrl;

    /**
     * @Cycle\Column(type = "string(3)", nullable = true, name = "default_currency_code")
     * @var string|null
     */
    public $defaultCurrencyCode;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $password;

    /**
     * @Cycle\Column(type = "datetime", name = "created_at")
     * @var \DateTimeImmutable
     */
    public $createdAt;

    /**
     * @Cycle\Column(type = "datetime", name = "updated_at")
     * @var \DateTimeImmutable
     */
    public $updatedAt;

    /**
     * @Cycle\Relation\BelongsTo(target = "App\Database\Currency", innerKey = "default_currency_code")
     * @var \App\Database\Currency
     */
    public $defaultCurrency;

    /**
     * @Cycle\Relation\ManyToMany(target = "App\Database\Wallet", though = "App\Database\UserWallet")
     * @var \Cycle\ORM\Relation\Pivoted\PivotedCollection
     */
    public $wallets;

    /**
     * User constructor.
     */
    public function __construct()
    {
       $this->defaultCurrency = new Currency();
       $this->wallets = new PivotedCollection();
    }
}
