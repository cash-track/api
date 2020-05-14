<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity(repository = "App\Repository\CurrencyRateRepository")
 */
class CurrencyRate
{
    /**
     * @Cycle\Column(type = "string(3)", primary = true)
     * @var string
     */
    public $code;

    /**
     * @Cycle\Column(type = "string(3)", name = "base_currency_code")
     * @var string
     */
    public $baseCurrencyCode;

    /**
     * @Cycle\Column(type = "decimal(8,4)")
     * @var double
     */
    public $rate;

    /**
     * @Cycle\Column(type = "datetime", name = "updated_at")
     * @var \DateTimeImmutable
     */
    public $updatedAt;

    /**
     * @Cycle\Relation\HasOne(target = "App\Database\Currency", fkAction="NO ACTION",  outerKey="code")
     * @var \App\Database\Currency
     */
    public $currency;

    /**
     * @Cycle\Relation\BelongsTo(target = "App\Database\Currency", innerKey = "base_currency_code")
     * @var \App\Database\Currency
     */
    public $baseCurrency;

    /**
     * CurrencyRate constructor.
     */
    public function __construct()
    {
        $this->currency = new Currency();
        $this->baseCurrency = new Currency();
    }
}
