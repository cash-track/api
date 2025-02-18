<?php

declare(strict_types=1);

namespace App\Service\Statistics;

use App\Database\Charge;
use App\Database\Currency;
use App\Database\User;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\WalletRepository;

final class ProfileStatistics
{
    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly ChargeRepository $chargeRepository
    ) {
    }

    public function getChargeFlow(User $user, Currency $currency): array
    {
        $walletIDs = array_map(function (Wallet $wallet) {
            return (int) $wallet->id;
        }, $this->walletRepository->findAllByUserPKByCurrencyCode((int) $user->id, (string) $currency->code));

        $data = [
            Charge::TYPE_INCOME => [
                'type' => Charge::TYPE_INCOME,
            ],
            Charge::TYPE_EXPENSE => [
                'type' => Charge::TYPE_EXPENSE,
            ],
        ];

        $metrics = [
            'total' => null,
            'lastYear' => (new \DateTimeImmutable())->sub(new \DateInterval('P1Y')),
            'lastQuarter' => (new \DateTimeImmutable())->sub(new \DateInterval('P3M')),
            'lastMonth' => (new \DateTimeImmutable())->sub(new \DateInterval('P1M')),
        ];

        foreach ($metrics as $metricName => $dateFrom) {
            foreach ($data as $type => &$value) {
                $value[$metricName] = $this->chargeRepository->sumTotalByTypeByCurrencyFromDate($type, $walletIDs, $dateFrom);
            }
        }

        return $data;
    }

    public function getCounters(User $user): array
    {
        return [
            'wallets' => $this->walletRepository->countAllByUserPK((int) $user->id),
            'walletsArchived' => $this->walletRepository->countArchivedByUserPK((int) $user->id),
            'charges' => $this->chargeRepository->countAllByUserPKByType((int) $user->id),
            'chargesIncome' => $this->chargeRepository->countAllByUserPKByType((int) $user->id, Charge::TYPE_INCOME),
        ];
    }
}
