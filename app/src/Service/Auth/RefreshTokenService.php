<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Auth\RefreshTokenStorageInterface;
use App\Database\User;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Auth\ActorProviderInterface;
use Spiral\Auth\AuthContext;
use Spiral\Auth\AuthContextInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Auth\TransportRegistry;

final class RefreshTokenService
{
    public function __construct(
        private readonly ActorProviderInterface $actorProvider,
        private readonly RefreshTokenStorageInterface $tokenStorage,
        private readonly TransportRegistry $transportRegistry
    ) {
    }

    public function getContextByRequest(ServerRequestInterface $request): AuthContextInterface
    {
        $context = new AuthContext($this->actorProvider);

        foreach ($this->transportRegistry->getTransports() as $name => $transport) {
            $tokenID = $transport->fetchToken($request);
            if ($tokenID == null) {
                continue;
            }

            $token = $this->tokenStorage->load($tokenID);
            if ($token === null) {
                continue;
            }

            $context->start($token, $name);

            return $context;
        }

        return $context;
    }

    public function getContextByToken(string $tokenID): AuthContextInterface
    {
        $context = new AuthContext($this->actorProvider);

        $token = $this->tokenStorage->load($tokenID);
        if ($token === null) {
            return $context;
        }

        $context->start($token, 'token');

        return $context;
    }

    public function createToken(User $user): TokenInterface
    {
        return $this->tokenStorage->create([
            'sub' => $user->id,
            'kind' => 'refresh',
        ]);
    }

    public function close(TokenInterface $token): void
    {
        $this->tokenStorage->delete($token);
    }
}
