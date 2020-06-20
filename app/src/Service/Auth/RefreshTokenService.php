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
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="refreshTokenService")
 */
class RefreshTokenService
{
    /**
     * @var \Spiral\Auth\ActorProviderInterface
     */
    private $actorProvider;

    /**
     * @var \App\Auth\RefreshTokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var \Spiral\Auth\TransportRegistry
     */
    private $transportRegistry;

    /**
     * RefreshTokenService constructor.
     *
     * @param \Spiral\Auth\ActorProviderInterface $actorProvider
     * @param \App\Auth\RefreshTokenStorageInterface $tokenStorage
     * @param \Spiral\Auth\TransportRegistry $transportRegistry
     */
    public function __construct(
        ActorProviderInterface $actorProvider,
        RefreshTokenStorageInterface $tokenStorage,
        TransportRegistry $transportRegistry
    ) {
        $this->actorProvider = $actorProvider;
        $this->tokenStorage = $tokenStorage;
        $this->transportRegistry = $transportRegistry;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Spiral\Auth\AuthContextInterface
     */
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

    /**
     * @param string $tokenID
     * @return \Spiral\Auth\AuthContextInterface
     */
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

    /**
     * @param \App\Database\User $user
     * @return \Spiral\Auth\TokenInterface
     */
    public function authenticate(User $user): TokenInterface
    {
        return $this->tokenStorage->create([
            'sub' => $user->id,
            'kind' => 'refresh',
        ]);
    }

    /**
     * @param \Spiral\Auth\TokenInterface $token
     */
    public function close(TokenInterface $token): void
    {
        $this->tokenStorage->delete($token);
    }
}
