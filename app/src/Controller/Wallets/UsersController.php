<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\User;
use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class UsersController extends Controller
{
    use PrototypeTrait;

    /**
     * @Route(route="/wallets/<id>/users", name="wallet.users.list", methods="GET", group="auth")
     *
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function users(int $id): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPKWithUsers($id, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->usersView->json($wallet->users->getValues());
    }

    /**
     * @Route(route="/wallets/<id>/users/<userId>", name="wallet.users.add", methods="PATCH", group="auth")
     *
     * @param int $id
     * @param int $userId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function patch(int $id, int $userId): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPKWithUsers($id, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $user = $this->users->findByPK($userId);

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
     * @param int $id
     * @param int $userId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete(int $id, int $userId): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPKWithUsers($id, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $user = $this->users->findByPK($userId);

        if (! $user instanceof User) {
            return $this->response->create(404);
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
