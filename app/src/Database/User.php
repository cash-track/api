<?php

declare(strict_types=1);

namespace App\Database;

use App\Security\PasswordContainerInterface;
use Cycle\Annotated\Annotation as Cycle;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Cycle\Entity(repository = "App\Repository\UserRepository", mapper = "App\Mapper\TimestampedMapper")
 * @Cycle\Table(indexes={
 *     @Cycle\Table\Index(columns = {"nick_name"}, unique = true),
 *     @Cycle\Table\Index(columns = {"email"}, unique = true)
 * })
 */
class User implements PasswordContainerInterface
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
     * @Cycle\Column(type = "string", nullable = true, name = "last_name")
     * @var string
     */
    public $lastName = '';

    /**
     * @Cycle\Column(type = "string", name = "nick_name")
     * @var string
     */
    public $nickName = '';

    /**
     * @Cycle\Column(type = "string")
     *
     * @var string
     */
    public $email = '';

    /**
     * @Cycle\Column(type = "boolean", default = "0", name = "is_email_confirmed")
     * @var bool
     */
    public $isEmailConfirmed = false;

    /**
     * @Cycle\Column(type = "string(255)", nullable = true, name = "photo")
     * @var string|null
     */
    public $photo;

    /**
     * @Cycle\Column(type = "string(3)", name = "default_currency_code")
     * @var string|null
     */
    public $defaultCurrencyCode;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $password = '';

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
     * @Cycle\Relation\BelongsTo(target = "App\Database\Currency", innerKey = "default_currency_code", cascade = false, load = "eager")
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
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    /**
     * @return string
     */
    public function fullName(): string
    {
        return "{$this->name} {$this->lastName}";
    }
}
