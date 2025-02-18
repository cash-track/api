<?php

declare(strict_types=1);

namespace App\Service\Statistics;

use App\Service\ChargeWalletService;

final class ChargeTotalData
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function format(): array
    {
        $result = [];

        foreach ($this->data as $item) {
            $result[] = [
                'amount' => ChargeWalletService::safeFloatNumber((float) $item['total']),
                'tags' => $this->parseIds($item['tag_ids']),
            ];
        }

        return $result;
    }

    private function parseIds(?string $ids): array
    {
        if ($ids === null) {
            return [];
        }

        return array_map(fn ($id) => (int) $id, explode(',', $ids));
    }
}
