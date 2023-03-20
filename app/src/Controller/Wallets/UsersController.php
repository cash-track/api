<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\User;
use App\Database\Wallet;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use App\Service\WalletService;
use App\View\UsersView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class UsersController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private UsersView $usersView,
        private UserRepository $userRepository,
        private WalletRepository $walletRepository,
        private WalletService $walletService,
    ) {
        parent::__construct($auth);
    }

    /**
     * @Route(route="/wallets/<id>/users", name="wallet.users.list", methods="GET", group="auth")
     *
     * @param string $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function users(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPKWithUsers((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->usersView->json($wallet->users->getValues());
    }

    /**
     * @Route(route="/wallets/<id>/users/<userId>", name="wallet.users.add", methods="PATCH", group="auth")
     *
     * @param string $id
     * @param string $userId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function patch(string $id, string $userId): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPKWithUsers((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        /** @var \App\Database\User|null $user */
        $user = $this->userRepository->findByPK((int) $userId);

        $this->logger->info("Debug patch user => " . print_r($user, true));

        if (! $user instanceof User) {
            return $this->response->create(404);
        }

        try {
            $this->walletService->share($wallet, $user, $this->user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to share wallet with user', [
                'action'   => 'wallet.users.add',
                'id'       => $wallet->id,
                'userId'   => $user->id,
                'sharerId' => $this->user->id,
                'msg'      => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to share wallet with user. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }

    /**
     * @Route(route="/wallets/<id>/users/<userId>", name="wallet.users.delete", methods="DELETE", group="auth")
     *
     * @param string $id
     * @param string $userId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete(string $id, string $userId): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPKWithUsers((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        /** @var \App\Database\User|null $user */
        $user = $this->userRepository->findByPK((int) $userId);

        $this->logger->info("Debug delete user => " . print_r($user, true));

        if (! $user instanceof User) {
            return $this->response->create(404);
        }

        if ($wallet->users->count() === 1) {
            if ($this->user->id === (int) $userId) {
                return $this->response->json([
                    'message' => 'Unable to revoke user from wallet. You are only one member. Delete wallet if you do not need them anymore.',
                    'error'   => 'Current user is the one wallet owner.',
                ], 403);
            }

            return $this->response->create(200);
        }

        try {
            $this->walletService->revoke($wallet, $user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to revoke user from wallet', [
                'action'    => 'wallet.users.delete',
                'id'        => $wallet->id,
                'userId'    => $user->id,
                'revokerId' => $this->user->id,
                'msg'       => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to revoke user from wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
