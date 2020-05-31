<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity(repository = "App\Repository\CurrencyRepository")
 */
class Currency
{
    const DEFAULT_CURRENCY_CODE = 'USD';

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
     * @Cycle\Column(type = "decimal(8,4)")
     * @var double
     */
    public $rate;

    /**
     * @Cycle\Column(type = "datetime", name = "updated_at")
     * @var \DateTimeImmutable
     */
    public $updatedAt;
}
