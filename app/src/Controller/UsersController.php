<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\User;
use App\Repository\UserRepository;
use App\View\UsersView;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class UsersController extends AuthAwareController
{
    public function __construct(
        AuthScope $authScope,
        protected ResponseWrapper $response,
        protected UserRepository $userRepository,
        protected UserView $userView,
        protected UsersView $usersView,
    ) {
        parent::__construct($authScope);
    }

    /**
     * @Route(route="/users/find/by-email/<query>", name="users.find.by-email", methods="GET", group="auth")
     *
     * @param string $query
     * @return \Psr\Http\Message\ResponseInterface
     */
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

    /**
     * @Route(route="/users/find/by-common-wallets", name="users.find.by-common-wallets", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function findByCommonWallets(): ResponseInterface
    {
        /** @var \App\Database\User[] $users */
        $users = $this->userRepository->findByCommonWallets($this->user);

        return $this->usersView->json($users);
    }
}
