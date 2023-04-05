<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AuthAwareController;
use App\Database\Currency;
use App\Repository\ChargeRepository;
use App\Repository\CurrencyRepository;
use App\Repository\WalletRepository;
use App\Service\Statistics\ProfileStatistics;
use App\View\CurrencyView;
use App\View\WalletsView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

class ProfileStatisticsController extends AuthAwareController
{
    use TranslatorTrait;

    #[Route(route: '/profile/statistics/charges-flow', name: 'profile.statistics.charges-flow', methods: 'GET', group: 'auth')]
    public function chargesFlow(
        ResponseWrapper $response,
        CurrencyRepository $currencyRepository,
        CurrencyView $currencyView,
        ProfileStatistics $statistics,
    ): ResponseInterface {
        /** @var \App\Database\Currency|null $currency */
        $currency = $currencyRepository->findByPK($this->user->defaultCurrencyCode ?? Currency::DEFAULT_CURRENCY_CODE);

        if (! $currency instanceof Currency) {
            return $response->json([
                'message' => $this->say('error_unknown_currency'),
            ], 400);
        }

        $data = $statistics->getChargeFlow($this->user, $currency);
        $data['currency'] = $currencyView->map($currency);

        return $response->json([
            'data' => $data,
        ]);
    }

    #[Route(route: '/profile/statistics/counters', name: 'profile.statistics.counters', methods: 'GET', group: 'auth')]
    public function counters(ResponseWrapper $response, ProfileStatistics $statistics): ResponseInterface
    {
        return $response->json([
            'data' => $statistics->getCounters($this->user),
        ]);
    }

    #[Route(route: '/profile/wallets/latest', name: 'profile.wallets.latest', methods: 'GET', group: 'auth')]
    public function walletsLatest(
        WalletRepository $walletRepository,
        ChargeRepository $chargeRepository,
        WalletsView $view
    ): ResponseInterface {
        $wallets = $walletRepository->findByUserPKLatestWithLimit((int) $this->user->id);

        foreach ($wallets as $wallet) {
            $wallet->setLatestCharges($chargeRepository->findByWalletIDLatest((int) $wallet->id));
        }

        return $view->json($wallets);
    }
}
