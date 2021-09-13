<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Database\Currency;
use App\Database\User;
use App\Service\Statistics\ProfileStatistics;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

class ProfileStatisticsController
{
    use PrototypeTrait;

    /**
     * @var \App\Database\User
     */
    protected $user;

    /**
     * ProfileStatisticsController constructor.
     *
     * @param \Spiral\Auth\AuthScope $auth
     */
    public function __construct(AuthScope $auth)
    {
        $user = $auth->getActor();

        if (! $user instanceof User) {
            throw new \RuntimeException('Unable to get authenticated user');
        }

        $this->user = $user;
    }

    /**
     * @Route(route="/profile/statistics/charges-flow", name="profile.statistics.charges-flow", methods="GET", group="auth")
     *
     * @param \App\Service\Statistics\ProfileStatistics $statistics
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function chargesFlow(ProfileStatistics $statistics): ResponseInterface
    {
        $currency = $this->currencies->findByPK($this->user->defaultCurrencyCode ?? Currency::DEFAULT_CURRENCY_CODE);

        if (! $currency instanceof Currency) {
            return $this->response->json([
                'message' => 'Unable to find currency.',
            ], 400);
        }

        $data = $statistics->getChargeFlow($this->user, $currency);
        $data['currency'] = $this->currencyView->map($currency);

        return $this->response->json([
            'data' => $data,
        ]);
    }
}
