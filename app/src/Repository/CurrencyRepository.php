<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Currency;
use Cycle\ORM\Select\Repository;
use Spiral\Database\Injection\Parameter;

class CurrencyRepository extends Repository
{
    const FEATURED_USD = 'USD';
    const FEATURED_EUR = 'EUR';
    const FEATURED_UAH = 'UAH';

    /**
     * Fetch default currency
     *
     * @return \App\Database\Currency|null
     */
    public function getDefault(): ?Currency
    {
        $currency = $this->findByPK(Currency::DEFAULT_CURRENCY_CODE);

        if ($currency instanceof Currency) {
            return $currency;
        }

        return null;
    }

    /**
     * @return \App\Database\Currency[]|object[]|array
     */
    public function getFeatured(): array
    {
        return $this->select()->where('code', 'in', new Parameter([
            self::FEATURED_EUR,
            self::FEATURED_USD,
            self::FEATURED_UAH,
        ]))->fetchAll();
    }
}
