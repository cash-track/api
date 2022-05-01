<?php

declare(strict_types=1);

namespace App;

use Cycle\Database\Injection\Parameter;
use Cycle\Migrations\Migration;

class InsertInitialCurrenciesMigration extends Migration
{
    /**
     * @var \string[][]
     */
    private $currencies = [
        ['USD', 'United States dollar', '$', 1.0, '2020-05-17 00:00:00'],
        ['EUR', 'European euro', '€', 0.92, '2020-05-17 00:00:00'],
        ['UAH', 'Ukrainian hryvnia', '₴', 26.63, '2020-05-17 00:00:00'],
    ];

    /**
     * Create tables, add columns or insert data here
     */
    public function up(): void
    {
        $insert = $this->database()
                       ->table('currencies')
                       ->insert()
                       ->columns(['code', 'name', 'char', 'rate', 'updated_at']);

        foreach ($this->currencies as $currency) {
            $insert->values($currency);
        }

        $insert->run();
    }

    /**
     * Drop created, columns and etc here
     */
    public function down(): void
    {
        $this->database()
             ->table('currencies')
             ->delete()
             ->where('code', 'in', new Parameter($this->getCodes()))
             ->run();
    }

    /**
     * Pluck currencies list to get only codes
     *
     * @return array
     */
    protected function getCodes(): array
    {
        return array_map(function (array $item): string {
            return $item[0];
        }, $this->currencies);
    }
}
