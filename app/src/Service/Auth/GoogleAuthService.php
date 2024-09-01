<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Database\Currency;
use App\Database\GoogleAccount;
use App\Database\User;
use App\Repository\CurrencyRepository;
use App\Repository\GoogleAccountRepository;
use App\Repository\UserRepository;
use App\Service\Auth\Exception\InvalidTokenException;
use App\Service\GoogleAccountService;
use App\Service\PhotoStorageService;
use App\Service\UserOptionsService;
use App\Service\UserService;
use Google;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Translator\Traits\TranslatorTrait;
use Spiral\Translator\Translator;

class GoogleAuthService extends AuthService
{
    use TranslatorTrait;

    public function __construct(
        protected AuthScope $auth,
        protected Google\Client $client,
        protected Translator $translator,
        protected LoggerInterface $logger,
        protected UserService $userService,
        protected UserRepository $userRepository,
        protected TokenStorageInterface $tokenStorage,
        protected UserOptionsService $userOptionsService,
        protected CurrencyRepository $currencyRepository,
        protected PhotoStorageService $photoStorageService,
        protected RefreshTokenService $refreshTokenService,
        protected GoogleAccountService $googleAccountService,
        protected EmailConfirmationService $emailConfirmationService,
        protected readonly GoogleAccountRepository $googleAccountRepository,
    ) {
        parent::__construct(
            $this->auth,
            $this->translator,
            $this->logger,
            $this->userService,
            $this->userRepository,
            $this->tokenStorage,
            $this->userOptionsService,
            $this->currencyRepository,
            $this->refreshTokenService,
            $this->googleAccountService,
            $this->emailConfirmationService,
        );
    }

    public function loginOrRegister(string $idToken): Authentication
    {
        /**
         * Example of $data returned by verifyIdToken()
         *
         * [
         *  "iss" => "https://accounts.google.com"
         *  "nbf" => 123456789
         *  "aud" => "x-y.apps.googleusercontent.com"
         *  "sub" => "123456789"
         *  "email" => "username@gmail.com"
         *  "email_verified" => true
         *  "azp" => "x-y.apps.googleusercontent.com"
         *  "name" => "Kerry King"
         *  "picture" => "https://lh3.googleusercontent.com/a/r-t=s96-c"
         *  "given_name" => "Kerry"
         *  "family_name" => "King"
         *  "iat" => 123456789
         *  "exp" => 123456789
         *  "jti" => "123456789"
         * ]
         */

        try {
            $data = $this->client->verifyIdToken($idToken);
        } catch (\Throwable $exception) {
            $this->logger->error('Unexpected error while verifying Google ID Token', [
                'isEmpty' => empty($idToken),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);

            throw new InvalidTokenException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if ($data === false) {
            $this->logger->info('Google ID Token is invalid', [
                'idToken' => $idToken,
            ]);

            throw new InvalidTokenException($this->say('google_auth_invalid_id_token'));
        }

        foreach (['sub', 'email', 'picture', 'given_name', 'family_name'] as $field) {
            if (! empty($data[$field])) {
                continue;
            }

            $this->logger->error("Google ID Token verification returned unexpected data. Empty field {$field}", [
                'data' => json_encode($data),
            ]);

            throw new InvalidTokenException($this->say('google_auth_id_token_not_verified'));
        }

        try {
            $user = $this->userRepository->findByEmail($data['email']);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to find user by email from Google ID Token', [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);

            throw new \RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if ($user instanceof User && ($data['email_verified'] ?? false) === false) {
            $this->logger->error('Unable to attach Google Account to existing user, Google email is not verified.', [
                'data' => json_encode($data),
            ]);

            throw new InvalidTokenException($this->say('google_auth_account_not_verified'));
        }

        if ($user instanceof User) {
            $googleAccount = $this->googleAccountRepository->findByUser($user);

            if ($googleAccount instanceof GoogleAccount && $googleAccount->accountId !== (string) ($data['sub'] ?? '')) {
                $this->logger->error('Unable to attach Google Account to existing user, Google account ID is already attached and different from actual.', [
                    'data' => json_encode($data),
                ]);

                throw new InvalidTokenException($this->say('google_auth_email_already_claimed'));
            }

            if ($googleAccount instanceof GoogleAccount) {
                // Update existing Google Account data
                $googleAccount->setData($data);
            } else {
                // attach existing user to the new Google Account
                $googleAccount = $this->makeGoogleAccount($user, $data);

                if ($user->photo === null) {
                    $this->photoStorageService->queueDownloadProfilePhoto((int) $user->id, $data['picture']);
                }
            }

            $this->storeUser($user);
            $this->storeGoogleAccount($googleAccount);
        } else {
            // new user
            $user = $this->makeUser($data);
            $user = $this->createUser($user);
            $googleAccount = $this->makeGoogleAccount($user, $data);
            $this->storeGoogleAccount($googleAccount);
            $this->photoStorageService->queueDownloadProfilePhoto((int) $user->id, $data['picture']);
        }

        return $this->authenticate($user);
    }

    protected function makeUser(array $data): User
    {
        $user = new User();
        $user->name = $data['given_name'] ?? null;
        $user->lastName = $data['family_name'] ?? null;
        $user->nickName = $this->makeSafeNickName($data['name'] ?? "$user->name $user->lastName");
        $user->email = $data['email'] ?? null;
        $user->defaultCurrencyCode = Currency::DEFAULT_CURRENCY_CODE;
        $user->isEmailConfirmed = (bool) ($data['email_verified'] ?? false);

        return $user;
    }

    protected function makeGoogleAccount(User $user, array $data): GoogleAccount
    {
        $account = new GoogleAccount();
        $account->userId = (int) $user->id;
        $account->accountId = $data['sub'] ?? null;
        $account->pictureUrl = $data['picture'] ?? null;
        $account->setData($data);

        return $account;
    }
}
