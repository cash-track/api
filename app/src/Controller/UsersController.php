<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\User;
use App\Repository\UserRepository;
use App\View\UsersView;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class UsersController extends AuthAwareController
{
    public function __construct(
        AuthContextInterface $auth,
        protected readonly ResponseWrapper $response,
        protected readonly UserRepository $userRepository,
        protected readonly UserView $userView,
        protected readonly UsersView $usersView,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/users/find/by-email/<query>', name: 'users.find.by-email', methods: 'GET', group: 'auth')]
    public function findByEmail(string $query): ResponseInterface
    {
        if ($query === $this->user->email) {
            return $this->response->json(['data' => null], 404);
        }

        $user = $this->userRepository->findByEmail($query);

        if (! $user instanceof User) {
            return $this->response->json(['data' => null], 404);
        }

        return $this->userView->json($user);
    }

    #[Route(route: '/users/find/by-common-wallets', name: 'users.find.by-common-wallets', methods: 'GET', group: 'auth')]
    public function findByCommonWallets(): ResponseInterface
    {
        /** @var \App\Database\User[] $users */
        $users = $this->userRepository->findByCommonWallets($this->user);

        return $this->usersView->json($users);
    }
}
