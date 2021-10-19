<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity(repository = "App\Repository\CurrencyExchangeRepository", mapper = "App\Mapper\TimestampedMapper")
 */
class CurrencyExchange
{
    /**
     * @Cycle\Column(type = "primary")
     * @var int|null
     */
    public $id;

    /**
     * @Cycle\Column(type = "string(3)", name = "src_currency_code")
     * @var string
     */
    public $srcCurrencyCode = '';

    /**
     * @Cycle\Column(type = "decimal(13,2)", name = "src_amount")
     * @var float
     */
    public $srcAmount = 0.0;

    /**
     * @Cycle\Column(type = "decimal(8,4)")
     * @var double
     */
    public $rate = 0.0;

    /**
     * @Cycle\Column(type = "string(3)", name = "dst_currency_code")
     * @var string
     */
    public $dstCurrencyCode = '';

    /**
     * @Cycle\Column(type = "decimal(13,2)", name = "dst_amount")
     * @var float
     */
    public $dstAmount = 0.0;

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
     * @Cycle\Relation\BelongsTo(target = "App\Database\Currency", innerKey = "src_currency_code")
     * @var \App\Database\Currency
     */
    public $srcCurrency;

    /**
     * @Cycle\Relation\BelongsTo(target = "App\Database\Currency", innerKey = "dst_currency_code")
     * @var \App\Database\Currency
     */
    public $dstCurrency;

    /**
     * CurrencyExchange constructor.
     */
    public function __construct()
    {
        $this->srcCurrency = new Currency();
        $this->dstCurrency = new Currency();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }
}
