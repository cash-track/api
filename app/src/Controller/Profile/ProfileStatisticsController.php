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
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

class ProfileStatisticsController extends AuthAwareController
{
    /**
     * @Route(route="/profile/statistics/charges-flow", name="profile.statistics.charges-flow", methods="GET", group="auth")
     *
     * @param \Spiral\Http\ResponseWrapper $response
     * @param \App\Repository\CurrencyRepository $currencyRepository
     * @param \App\View\CurrencyView $currencyView
     * @param \App\Service\Statistics\ProfileStatistics $statistics
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function chargesFlow(
        ResponseWrapper $response,
        CurrencyRepository $currencyRepository,
        CurrencyView $currencyView,
        ProfileStatistics $statistics,
    ): ResponseInterface {
        $currency = $currencyRepository->findByPK($this->user->defaultCurrencyCode ?? Currency::DEFAULT_CURRENCY_CODE);

        if (! $currency instanceof Currency) {
            return $response->json([
                'message' => 'Unable to find currency.',
            ], 400);
        }

        $data = $statistics->getChargeFlow($this->user, $currency);
        $data['currency'] = $currencyView->map($currency);

        return $response->json([
            'data' => $data,
        ]);
    }

    /**
     * @Route(route="/profile/statistics/counters", name="profile.statistics.counters", methods="GET", group="auth")
     *
     * @param \Spiral\Http\ResponseWrapper $response
     * @param \App\Service\Statistics\ProfileStatistics $statistics
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function counters(ResponseWrapper $response, ProfileStatistics $statistics): ResponseInterface
    {
        return $response->json([
            'data' => $statistics->getCounters($this->user),
        ]);
    }

    /**
     * @Route(route="/profile/wallets/latest", name="profile.wallets.latest", methods="GET", group="auth")
     *
     * @param \App\Repository\WalletRepository $walletRepository
     * @param \App\Repository\ChargeRepository $chargeRepository
     * @param \App\View\WalletsView $view
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function walletsLatest(
        WalletRepository $walletRepository,
        ChargeRepository $chargeRepository,
        WalletsView $view
    ): ResponseInterface {
        $wallets = $walletRepository->findByUserPKLatestWithLimit((int) $this->user->id);

        foreach ($wallets as $wallet) {
            $wallet->latestCharges = new ArrayCollection($chargeRepository->findByWalletIDLatest((int) $wallet->id));
        }

        return $view->json($wallets);
    }
}
