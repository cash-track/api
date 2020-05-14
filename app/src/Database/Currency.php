<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity(repository = "App\Repository\CurrencyRepository")
 */
class Currency
{
    /**
     * @Cycle\Column(type = "string(3)", primary = true)
     * @var string
     */
    public $code;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $name;

    /**
     * @Cycle\Column(type = "string(1)")
     * @var string
     */
    public $char;

    /**
     * @Cycle\Relation\HasOne(target = "App\Database\CurrencyRate", outerKey = "code")
     * @var \App\Database\CurrencyRate
     */
    public $rate;

    /**
     * Currency constructor.
     */
    public function __construct()
    {
        $this->rate = new CurrencyRate();
    }
}
