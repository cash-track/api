<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AuthAwareController;
use App\Database\Passkey;
use App\Repository\PasskeyRepository;
use App\Request\Profile\InitPasskeyRequest;
use App\Request\Profile\StorePasskeyRequest;
use App\Service\Auth\Passkey\PasskeyService;
use App\View\PasskeysView;
use App\View\PasskeyView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class PasskeyController extends AuthAwareController
{
    use TranslatorTrait;

    public function __construct(
        AuthContextInterface $auth,
        protected readonly PasskeyView $passkeyView,
        protected readonly ResponseWrapper $response,
        protected readonly PasskeysView $passkeysView,
        protected readonly PasskeyService $passkeyAuthService,
        protected readonly PasskeyRepository $passkeyRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/profile/passkey', name: 'profile.passkey.list', methods: 'GET', group: 'auth')]
    public function list(): ResponseInterface
    {
        return $this->passkeysView->json($this->passkeyRepository->findAllByUserPK((int) $this->user->id));
    }

    #[Route(route: '/profile/passkey/init', name: 'profile.passkey.init', methods: 'POST', group: 'auth')]
    public function init(InitPasskeyRequest $request): ResponseInterface
    {
        try {
            $response = $this->passkeyAuthService->init($this->user, $request->name);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('passkey_init_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->json($response);
    }

    #[Route(route: '/profile/passkey', name: 'profile.passkey.create', methods: 'POST', group: 'auth')]
    public function store(StorePasskeyRequest $request): ResponseInterface
    {
        try {
            $passkey = $this->passkeyAuthService->store($this->user, $request->challenge, $request->data);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('passkey_store_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->passkeyView->json($passkey);
    }

    #[Route(route: '/profile/passkey/<id>', name: 'profile.passkey.delete', methods: 'DELETE', group: 'auth')]
    public function delete(string $id): ResponseInterface
    {
        $passkey = $this->passkeyRepository->findByPKAndUserPK((int) $id, (int) $this->user->id);

        if (! $passkey instanceof Passkey) {
            return $this->response->create(404);
        }

        try {
            $this->passkeyAuthService->delete($passkey);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('passkey_delete_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->json([
            'message' => $this->say('passkey_deleted'),
        ]);
    }
}
