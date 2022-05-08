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
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

class ProfileController extends AuthAwareController
{
    /**
     * @param \Spiral\Auth\AuthScope $auth
     * @param \App\View\UserView $userView
     * @param \Psr\Log\LoggerInterface $logger
     * @param \App\Service\UserService $userService
     * @param \Spiral\Http\ResponseWrapper $response
     * @param \App\Repository\CurrencyRepository $currencyRepository
     */
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

    /**
     * @Route(route="/profile", name="profile.index", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(): ResponseInterface
    {
        return $this->userView->json($this->user);
    }

    /**
     * @Route(route="/profile/check/nick-name", name="profile.check.nickname", methods="POST", group="auth")
     *
     * @param \App\Request\CheckNickNameRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function checkNickName(CheckNickNameRequest $request): ResponseInterface
    {
        $request->setContext($this->user);

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        return $this->response->json([
            'message' => 'Nick name are free to use'
        ]);
    }

    /**
     * @Route(route="/profile", name="profile.update", methods="PUT", group="auth")
     *
     * @param \App\Request\Profile\UpdateBasicRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(UpdateBasicRequest $request): ResponseInterface
    {
        $request->setContext($this->user);

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $this->user->name = $request->getName();
        $this->user->lastName = $request->getLastName();
        $this->user->nickName = $request->getNickName();
        $this->user->defaultCurrencyCode = $request->getDefaultCurrencyCode();

        try {
            /** @var \App\Database\Currency|null $defaultCurrency */
            $defaultCurrency = $this->currencyRepository->findByPK($request->getDefaultCurrencyCode());

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
