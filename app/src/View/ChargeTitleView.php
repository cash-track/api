<?php

declare(strict_types=1);

namespace App\View;

use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
final class ChargeTitleView
{
    public function __construct(
        protected ResponseWrapper $response,
    ) {
    }

    public function map(?array $chargeTitle): ?array
    {
        if ($chargeTitle === null || ($chargeTitle['title'] ?? null) === null) {
            return null;
        }

        return [
            'type'  => 'chargeTitle',
            'title' => $chargeTitle['title'] ?? null,
            'count' => $chargeTitle['count'] ?? 0,
        ];
    }
}
