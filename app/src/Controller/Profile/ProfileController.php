<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AuthAwareController;
use App\Database\Currency;
use App\Repository\CurrencyRepository;
use App\Request\CheckNickNameRequest;
use App\Request\Profile\UpdateBasicRequest;
use App\Service\UserService;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Validation\ValidationProviderInterface;
use Spiral\Validator\FilterDefinition;

class ProfileController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        protected UserView $userView,
        protected LoggerInterface $logger,
        protected UserService $userService,
        protected ResponseWrapper $response,
        protected CurrencyRepository $currencyRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/profile', name: 'profile.index', methods: 'GET', group: 'auth')]
    public function index(): ResponseInterface
    {
        return $this->userView->json($this->user);
    }

    #[Route(route: '/profile/check/nick-name', name: 'profile.check.nickname', methods: 'POST', group: 'auth')]
    public function checkNickName(CheckNickNameRequest $_): ResponseInterface
    {
        return $this->response->json([
            'message' => 'Nick name are free to use'
        ]);
    }

    #[Route(route: '/profile', name: 'profile.update', methods: 'PUT', group: 'auth')]
    public function update(UpdateBasicRequest $request): ResponseInterface
    {
        $this->user->name = $request->name;
        $this->user->lastName = $request->lastName;
        $this->user->nickName = $request->nickName;
        $this->user->defaultCurrencyCode = $request->defaultCurrencyCode;

        try {
            /** @var \App\Database\Currency|null $defaultCurrency */
            $defaultCurrency = $this->currencyRepository->findByPK($request->defaultCurrencyCode);

            if (! $defaultCurrency instanceof Currency) {
                throw new \RuntimeException('Unable to load default currency');
            }

            $this->user->setDefaultCurrency($defaultCurrency);
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to load currency entity', [
                'action' => 'profile.update',
                'id'     => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);
        }

        try {
            $this->userService->store($this->user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store user', [
                'action' => 'profile.update',
                'id'     => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update basic user profile. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->userView->json($this->user);
    }
}
