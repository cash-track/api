<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Database\Currency;
use App\Database\User;
use App\Repository\CurrencyRepository;
use App\Repository\UserRepository;
use App\Security\PasswordContainerInterface;
use App\Service\UserOptionsService;
use App\Service\UserService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Auth\TokenInterface;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Translator\Traits\TranslatorTrait;
use Spiral\Translator\Translator;

class AuthService
{
    use TranslatorTrait;

    const RANDOM_PASSWORD_LENGTH = 32;

    public function __construct(
        protected AuthScope $auth,
        protected Translator $translator,
        protected LoggerInterface $logger,
        protected UserService $userService,
        protected UserRepository $userRepository,
        protected TokenStorageInterface $tokenStorage,
        protected UserOptionsService $userOptionsService,
        protected CurrencyRepository $currencyRepository,
        protected RefreshTokenService $refreshTokenService,
        protected EmailConfirmationService $emailConfirmationService,
    ) {
    }

    public function login(string $email, string $password): ?Authentication
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user instanceof User) {
            return null;
        }

        if (! $this->verifyPassword($user, $password)) {
            return null;
        }

        return $this->authenticate($user);
    }

    public function logout(string $refreshToken = ''): void
    {
        $this->auth->close();

        $token = $this->refreshTokenService->getContextByToken($refreshToken)->getToken();

        if ($token instanceof TokenInterface) {
            $this->refreshTokenService->close($token);
        }
    }

    public function register(User $user, string $locale): Authentication
    {
        $user = $this->createUser($user, $locale);

        return $this->authenticate($user);
    }

    public function refresh(ServerRequestInterface $request): ?Authentication
    {
        $authContext = $this->refreshTokenService->getContextByRequest($request);

        $user = $authContext->getActor();
        $refreshToken = $authContext->getToken();

        if (! $user instanceof User || ! $refreshToken instanceof TokenInterface) {
            return null;
        }

        // TODO. Add to blacklist token $refreshToken->getID();
        // TODO. Add to blacklist token $refreshTokenRequest->getAccessToken();

        return $this->authenticate($user);
    }

    public function updatePassword(User $user, string $password): void
    {
        $this->hashPassword($user, $password);

        try {
            $this->userService->store($user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store user while update password', [
                'userId' => $user->id,
                'message'    => $exception->getMessage(),
            ]);

            throw $exception;
        }

        // TODO. End here all active sessions except current.

        // TODO. Add active token to blacklist, generate new and add to response.
    }

    public function hashPassword(PasswordContainerInterface $container, string $password): void
    {
        $container->setPasswordHash(password_hash($password, PASSWORD_ARGON2ID));
    }

    public function verifyPassword(PasswordContainerInterface $container, string $password): bool
    {
        return password_verify($password, $container->getPasswordHash());
    }

    protected function createUser(User $user, string $locale = null): User
    {
        $currency = $this->currencyRepository->getDefault();

        if ($currency instanceof Currency) {
            $user->setDefaultCurrency($currency);
        }

        if ($user->password !== '') {
            $this->hashPassword($user, $user->password);
        } else {
            $this->setRandomPassword($user);
        }

        $this->userOptionsService->setLocale($user, $locale ?? $this->translator->getLocale());

        if (($googleAccount = $user->googleAccount) !== null) {
            // insert user first
            $user->googleAccount = null;
            $this->storeUser($user);
            $user->googleAccount = $googleAccount;
        }

        $this->storeUser($user);

        if (! $user->isEmailConfirmed) {
            $this->initiateEmailConfirmation($user);
        }

        return $user;
    }

    protected function storeUser(User $user): User
    {
        try {
            return $this->userService->store($user);
        } catch (\Throwable $exception) {
            $this->logger->error('Error while storing user', [
                'error' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);

            throw new \RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    protected function initiateEmailConfirmation(User $user): void
    {
        try {
            $this->emailConfirmationService->create($user);
        } catch (\Throwable $exception) {
            $this->logger->error('Error while creating email confirmation request', [
                'error' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);
        }
    }

    public function authenticate(User $user): Authentication
    {
        return new Authentication(
            $user,
            $this->createToken($user),
            $this->refreshTokenService->createToken($user),
        );
    }

    protected function setRandomPassword(PasswordContainerInterface $container): void
    {
        $container->setPasswordHash(bin2hex(random_bytes(self::RANDOM_PASSWORD_LENGTH)));
    }

    protected function createToken(User $user): TokenInterface
    {
        $token = $this->tokenStorage->create([
            'sub' => $user->id,
        ]);

        $this->auth->start($token);

        return $token;
    }

    protected function makeSafeNickName(string $string, int $attempts = 10): string
    {
        $name = str_slug($string);
        $tag = '';

        while ($attempts > 0) {
            $nickName = $name . $tag;

            $user = $this->userRepository->findByNickName($nickName);
            if (! $user instanceof User) {
                return $nickName;
            }

            $tag = '-' . str_rand(3);
            $attempts--;
        }

        throw new \RuntimeException($this->say('auth_no_free_nickname'));
    }
}
