<?php

namespace App\Service\Limit;

use App\Database\Limit;

readonly class WalletLimit
{
    public float $percentage;

    public function __construct(public Limit $limit, public float $amount = 0)
    {
        $this->percentage = round(($this->amount / $this->limit->amount) * 100, 2);
    }
}
