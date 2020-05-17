<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Currency;
use Cycle\ORM\Select\Repository;

class CurrencyRepository extends Repository
{
    /**
     * Fetch default currency
     *
     * @return \App\Database\Currency|null
     */
    public function getDefault():? Currency
    {
        $currency = $this->findByPK(Currency::DEFAULT_CURRENCY_CODE);

        if ($currency instanceof Currency) {
            return $currency;
        }

        return null;
    }
}
