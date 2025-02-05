<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey;

use App\Config\PasskeyConfig;
use App\Database\Passkey;
use App\Database\User;
use App\Repository\PasskeyRepository;
use App\Repository\UserRepository;
use App\Service\Auth\Passkey\Exception\InvalidChallengeException;
use App\Service\Auth\Passkey\Exception\InvalidClientResponseException;
use App\Service\Auth\Passkey\Exception\PasskeyNotFoundException;
use App\Service\Auth\Passkey\Exception\UserNotFoundException;
use App\Service\Auth\Passkey\Response\DataEncoder;
use App\Service\Auth\Passkey\Response\PasskeyInitResponse;
use Cose\Algorithm\Manager;
use Cycle\ORM\EntityManagerInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Redis;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class PasskeyService
{
    use DataEncoder;

    const int CHALLENGE_TTL_SEC = 60 * 6; // 6 minutes

    public function __construct(
        private readonly Redis $redis,
        private readonly PasskeyConfig $config,
        private readonly EntityManagerInterface $tr,
        private readonly PasskeyRepository $repository,
        private readonly UserRepository $userRepository,
    ) {
    }

    protected function getAlgorithmManager(): Manager
    {
        $manager = Manager::create();

        foreach ($this->config->getSupportedAlgorithms() as $algorithm) {
            $manager->add($algorithm);
        }

        return $manager;
    }

    protected function getAttestationStatementSupportManager(): AttestationStatementSupportManager
    {
        $manager = new AttestationStatementSupportManager();
        $manager->add(NoneAttestationStatementSupport::create());

        return $manager;
    }

    protected function getSerializer(): SerializerInterface
    {
        $manager = new AttestationStatementSupportManager();
        $manager->add(NoneAttestationStatementSupport::create());

        return (new WebauthnSerializerFactory($this->getAttestationStatementSupportManager()))->create();
    }

    public function initAuth(): \JsonSerializable
    {
        $challenge = $this->generateChallenge();

        $options = PublicKeyCredentialRequestOptions::create(
            challenge: Base64UrlSafe::decodeNoPadding($challenge),
            rpId: $this->config->getServiceId(),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: $this->config->getTimeout(),
            extensions: $this->getExtensions(),
        );

        $this->storeRequestOptions($challenge, $options);

        return PasskeyInitResponse::create($challenge, $options->jsonSerialize());
    }

    /**
     * @param string $challenge
     * @param string $data
     * @return \App\Database\User
     * @throws \JsonException
     * @throws \Throwable
     * @throws \Webauthn\Exception\WebauthnException
     * @throws \App\Service\Auth\Passkey\Exception\InvalidChallengeException
     * @throws \App\Service\Auth\Passkey\Exception\InvalidClientResponseException
     * @throws \App\Service\Auth\Passkey\Exception\PasskeyNotFoundException
     * @throws \App\Service\Auth\Passkey\Exception\UserNotFoundException
     */
    public function authenticate(string $challenge, string $data): User
    {
        $options = $this->getRequestOptions($challenge);
        if (! ($options instanceof RequestChallenge) || ! ($options->options instanceof PublicKeyCredentialRequestOptions)) {
            throw new InvalidChallengeException('Invalid challenge');
        }

        try {
            $credential = $this->getSerializer()->deserialize(self::decode($data), PublicKeyCredential::class, 'json');
            $credential->response instanceof AuthenticatorAssertionResponse || throw new \RuntimeException('Invalid client response');
        } catch (\Throwable $exception) {
            throw new InvalidClientResponseException(
                'Invalid client data',
                (int) $exception->getCode(),
                $exception,
            );
        }

        $passkey = $this->repository->findKeyByCredential($credential);
        if (!$passkey instanceof Passkey || $passkey->data === '') {
            throw new PasskeyNotFoundException('Unregistered passkey');
        }

        $existingCredentialSource = $this->getSerializer()->deserialize($passkey->data, PublicKeyCredentialSource::class, 'json');

        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            null,
            null,
            ExtensionOutputCheckerHandler::create(),
            $this->getAlgorithmManager()
        );

        $credentialSource = $authenticatorAssertionResponseValidator->check(
            credentialId: $existingCredentialSource,
            authenticatorAssertionResponse: $credential->response,
            publicKeyCredentialRequestOptions: $options->options,
            request: $this->config->getServiceId(),
            userHandle: null,
        );

        $user = $this->userRepository->findByPK($passkey->userId);
        if (! $user instanceof User) {
            throw new UserNotFoundException('User not found');
        }

        $this->update($passkey, $credentialSource);
        $this->forgetOptions($challenge);

        return $user;
    }

    public function store(User $user, string $challenge, string $data): Passkey
    {
        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            $this->getAttestationStatementSupportManager(),
            null,
            null,
            ExtensionOutputCheckerHandler::create(),
        );

        $credential = $this->getSerializer()->deserialize(self::decode($data), PublicKeyCredential::class, 'json');
        $credential->response instanceof AuthenticatorAttestationResponse || throw new \RuntimeException('Invalid response data');

        $creationOptions = $this->getCreationOptions($challenge);
        (
            $creationOptions instanceof CreationChallenge &&
            $creationOptions->options instanceof PublicKeyCredentialCreationOptions
        ) || throw new \RuntimeException('Invalid challenge');

        $credentialSource = $authenticatorAttestationResponseValidator->check(
            $credential->response,
            $creationOptions->options,
            $this->config->getServiceId(),
        );

        $passkey = $this->persist($user, $creationOptions, $credentialSource);

        $this->forgetOptions($challenge);

        return $passkey;
    }

    public function persist(User $user, CreationChallenge $creation, PublicKeyCredentialSource $credential): Passkey
    {
        $passkey = new Passkey();
        $passkey->name = $creation->name;
        $passkey->setUser($user);
        $passkey->setData($credential->jsonSerialize());

        $this->tr->persist($passkey);
        $this->tr->run();

        return $passkey;
    }

    protected function update(Passkey $passkey, PublicKeyCredentialSource $credential): Passkey
    {
        $passkey->usedAt = new \DateTimeImmutable();
        $passkey->setData($credential->jsonSerialize());

        $this->tr->persist($passkey);
        $this->tr->run();

        return $passkey;
    }

    public function init(User $user, string $keyName): \JsonSerializable
    {
        $challenge = $this->generateChallenge();

        // The $options object is defined according to schema defined in WebAuthn interface.
        // @see https://w3c.github.io/webauthn/#dictdef-publickeycredentialcreationoptionsjson
        // @see https://github.com/MasterKale/SimpleWebAuthn/blob/master/packages/types/src/index.ts#L56
        $options = PublicKeyCredentialCreationOptions::create(
            rp: $this->getRelyingParty(),
            user: $this->getUserEntity($user),
            challenge: $challenge,
            pubKeyCredParams: $this->config->getSupportedPublicKeyCredentials(),
            authenticatorSelection: $this->getAuthenticatorSelectionCriteria(),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $this->getExistingCredentials($user),
            timeout: $this->config->getTimeout(),
            extensions: $this->getExtensions(),
        );

        $this->storeCreationOptions($keyName, $challenge, $options);

        return PasskeyInitResponse::create($challenge, $options->jsonSerialize());
    }

    public function delete(Passkey $passkey): void
    {
        $this->tr->delete($passkey);
        $this->tr->run();
    }

    protected function generateChallenge(): string
    {
        return str_rand(32);
    }

    protected function storeCreationOptions(string $name, string $challenge, PublicKeyCredentialCreationOptions $options): void
    {
        $creation = new CreationChallenge($this->getSerializer(), $name, $challenge, $options);

        $cacheKey = $this->challengeCacheKey($challenge);

        $this->redis->hMSet($cacheKey, $creation->toArray());
        $this->redis->expire($cacheKey, self::CHALLENGE_TTL_SEC);
    }

    protected function storeRequestOptions(string $challenge, PublicKeyCredentialRequestOptions $options): void
    {
        $request = new RequestChallenge($this->getSerializer(), $challenge, $options);

        $cacheKey = $this->challengeCacheKey($challenge);

        $this->redis->hMSet($cacheKey, $request->toArray());
        $this->redis->expire($cacheKey, self::CHALLENGE_TTL_SEC);
    }

    protected function forgetOptions(string $challenge): void
    {
        $cacheKey = $this->challengeCacheKey($challenge);
        $this->redis->del($cacheKey);
    }

    protected function getCreationOptions(string $challenge): ?CreationChallenge
    {
        $data = $this->redis->hGetAll($this->challengeCacheKey($challenge));
        if (! is_array($data)) {
            return null;
        }

        return CreationChallenge::fromArray($data, $this->getSerializer());
    }

    protected function getRequestOptions(string $challenge): ?RequestChallenge
    {
        $data = $this->redis->hGetAll($this->challengeCacheKey($challenge));
        if (! is_array($data)) {
            return null;
        }

        return RequestChallenge::fromArray($data, $this->getSerializer());
    }

    protected function challengeCacheKey(string $challenge): string
    {
        return "passkeys:challenge:{$challenge}";
    }

    protected function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return PublicKeyCredentialUserEntity::create(
            $user->email,
            (string) $user->id,
            $user->fullName(),
        );
    }

    /**
     * @param \App\Database\User $user
     * @return PublicKeyCredentialDescriptor[]
     */
    protected function getExistingCredentials(User $user): array
    {
        $keys = $this->repository->findAllByUserPK((int) $user->id);

        return array_map(function ($key) {
            $data = $key->getData();
            return PublicKeyCredentialDescriptor::create(
                type: $data['type'] ?? '',
                id: $key->keyId,
                transports: $data['transports'] ?? [],
            );
        }, $keys);
    }

    protected function getRelyingParty(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            $this->config->getServiceName(),
            $this->config->getServiceId(),
        );
    }

    protected function getAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        return AuthenticatorSelectionCriteria::create(
            // tells the client / authenticator to request user verification where possible
            // e.g. biometric or device PIN, the default option
            // @see https://w3c.github.io/webauthn/#dom-authenticatorselectioncriteria-userverification
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            // tells the authenticator to create a passkey
            // @see https://w3c.github.io/webauthn/#dom-authenticatorselectioncriteria-residentkey
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );
    }

    protected function getExtensions(): AuthenticationExtensions
    {
        return AuthenticationExtensions::create([
            AuthenticationExtension::create('credProps', true)
        ]);
    }
}
