<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Currency;
use Cycle\ORM\Select\Repository;
use Cycle\Database\Injection\Parameter;

/**
 * @extends Repository<\App\Database\Currency>
 */
class CurrencyRepository extends Repository
{
    const string FEATURED_USD = 'USD';
    const string FEATURED_EUR = 'EUR';
    const string FEATURED_UAH = 'UAH';

    /**
     * Fetch default currency
     *
     * @return \App\Database\Currency|null
     */
    public function getDefault(): ?Currency
    {
        /** @var \App\Database\Currency|null $currency */
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
