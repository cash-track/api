<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity(repository = "App\Repository\ChargeRepository", mapper = "App\Mapper\TimestampedUUIDMapper")
 */
class Charge
{
    const TYPE_INCOME  = '+';
    const TYPE_EXPENSE = '-';

    /**
     * @Cycle\Column(type = "string(36)", primary = true)
     * @var string
     */
    public $id = '';

    /**
     * @Cycle\Column(type = "int", name = "wallet_id")
     * @var int
     */
    public $walletId = 0;

    /**
     * @Cycle\Column(type = "int", name = "user_id")
     * @var int
     */
    public $userId = 0;

    /**
     * @Cycle\Column(type = "enum(+,-)", default = "+")
     * @var string
     */
    public $type = '';

    /**
     * @Cycle\Column(type = "decimal(13,2)")
     * @var float
     */
    public $amount = 0.0;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $title = '';

    /**
     * @Cycle\Column(type = "int", name = "currency_exchange_id", nullable = false)
     * @var int|null
     */
    public $currencyExchangeId = null;

    /**
     * @Cycle\Column(type = "text")
     * @var string
     */
    public $description = '';

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
     * @Cycle\Relation\BelongsTo(target = "App\Database\Wallet")
     * @var \App\Database\Wallet
     */
    public $wallet;

    /**
     * @Cycle\Relation\BelongsTo(target = "App\Database\User")
     * @var \App\Database\User
     */
    public $user;

    /**
     * @Cycle\Relation\BelongsTo(target = "App\Database\CurrencyExchange", nullable = false, fkAction="SET NULL", innerKey = "currency_exchange_id")
     * @var \App\Database\CurrencyExchange|null
     */
    public $currencyExchange;

    /**
     * Charge constructor.
     */
    public function __construct()
    {
        $this->wallet = new Wallet();
        $this->user = new User();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }
}
