<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use App\Config\PasskeyConfig;
use App\Database\Passkey;
use App\Database\User;
use App\Repository\PasskeyRepository;
use App\Repository\UserRepository;
use App\Service\Auth\Passkey\PasskeyService;
use Cycle\ORM\EntityManagerInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Factories\PasskeyFactory;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\TrustPath\EmptyTrustPath;

trait PasskeyServiceMocker
{
    protected function makePasskeyAuthMock(array $methods = [], ?callable $expectation = null): MockObject|PasskeyService
    {
        $redis = $this->getMockBuilder(\Redis::class)
                      ->disableOriginalConstructor()
                      ->getMock();

        $config = $this->getContainer()->get(PasskeyConfig::class);

        $tr = $this->getContainer()->get(EntityManagerInterface::class);

        $repository = $this->getContainer()->get(PasskeyRepository::class);

        $userRepository = $this->getContainer()->get(UserRepository::class);

        $passkeyAuthService = $this->getMockBuilder(PasskeyService::class)
                                   ->setConstructorArgs([
                                       $redis, $config, $tr, $repository, $userRepository,
                                   ])
                                   ->onlyMethods($methods)
                                   ->getMock();

        if ($expectation !== null) {
            $expectation($redis, $config, $tr, $repository, $userRepository);
        }

        $this->getContainer()->bind(PasskeyService::class, fn () => $passkeyAuthService);

        return $passkeyAuthService;
    }

    protected function makeCreationChallengeOptions(string $challenge, User $user, array $existingKeys = []): array
    {
        /** @var PasskeyConfig $config */
        $config = $this->getContainer()->get(PasskeyConfig::class);

        return [
            'rp' => [
                'name' => $config->getServiceName(),
                'id' => $config->getServiceId(),
            ],
            'user' => [
                'name' => $user->email,
                'id' => Base64UrlSafe::encodeUnpadded((string) $user->id),
                'displayName' => $user->fullName(),
            ],
            'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
            'pubKeyCredParams' => array_map(fn (PublicKeyCredentialParameters $item) => [
                'type' => $item->type,
                'alg' => $item->alg,
            ], $config->getSupportedPublicKeyCredentials()),
            'timeout' => $config->getTimeout(),
            'excludeCredentials' => array_map(fn (Passkey $key) => [
                'type' => 'public-key',
                'id' => $key->keyId,
                'transports' => ['internal', 'hybrid'],
            ], $existingKeys),
            'authenticatorSelection' => [
                'requireResidentKey' => false,
                'userVerification' => 'preferred',
                'residentKey' => 'required',
            ],
            'attestation' => 'none',
            'extensions' => [
                'credProps' => true,
            ],
        ];
    }

    protected function makeCreateData(array $options, Passkey $passkey): string
    {
        return Base64UrlSafe::encodeUnpadded(json_encode([
            'id' => Base64UrlSafe::encodeUnpadded($passkey->keyId),
            'rawId' => Base64UrlSafe::encodeUnpadded($passkey->keyId),
            'type' => 'public-key',
            'clientExtensionResults' => [],
            'authenticatorAttachment' => 'platform',
            'response' => [
                'attestationObject' => 'o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YViULBItvGCVFX4G0nMz5Wx_Lfv0OEvG2heV28UUqW8JHNZdAAAAALraVWanqkAfvZZFYZpVEg0AEK0_dVp61FIroaKBgp0WbP2lAQIDJiABIVggmJEcdulBPgAZsH-17G4p-sy0pKo3NiVRiaYgVCOFK-siWCB4LWrK96Gx27_frXCAoWkQBuRkPwbuKI1HGaHzmSnT3A',
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded(json_encode([
                    'type' => 'webauthn.create',
                    'challenge' => $options['challenge'] ?? '',
                    'origin' => 'https://my.dev-cash-track.app',
                    'crossOrigin' => false,
                ])),
                'transports' => ['internal', 'hybrid'],
                'publicKeyAlgorithm' => -7,
                'publicKey' => 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEmJEcdulBPgAZsH-17G4p-sy0pKo3NiVRiaYgVCOFK-t4LWrK96Gx27_frXCAoWkQBuRkPwbuKI1HGaHzmSnT3A',
                'authenticatorData' => 'LBItvGCVFX4G0nMz5Wx_Lfv0OEvG2heV28UUqW8JHNZdAAAAALraVWanqkAfvZZFYZpVEg0AEK0_dVp61FIroaKBgp0WbP2lAQIDJiABIVggmJEcdulBPgAZsH-17G4p-sy0pKo3NiVRiaYgVCOFK-siWCB4LWrK96Gx27_frXCAoWkQBuRkPwbuKI1HGaHzmSnT3A',
            ],
        ]));
    }

    protected function makePasskeyWithData(): Passkey
    {
        $passkey = PasskeyFactory::make();
        $passkey->keyId = 'Df9bqvrQ_ZX0Q3Xg5NIOHA';
        $passkey->setData([
            'publicKeyCredentialId' => 'Df9bqvrQ_ZX0Q3Xg5NIOHA',
            'type' => 'public-key',
            'transports' => ['internal', 'hybrid'],
            'attestationType' => 'none',
            'trustPath' => [
                'type' => EmptyTrustPath::class,
            ],
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'credentialPublicKey' => 'pQECAyYgASFYIMKFlYRvFIn52LAAOS3a0PPfLo9uDpc3dj8KiPcwYmPyIlggRbbxN5L0JVr-2fdMnqjJhseM4vNTlbUL8mdx-en5OII',
            'userHandle' => 'MQ',
            'counter' => 0,
            'otherUI' => null,
        ]);

        return $passkey;
    }

    protected function makeRequestChallengeOptions(): array
    {
        return [
            'challenge' => '80fc155b3dae2dcddcc82a3a6b386e55945fd4f0e460257b5b6cc223c448029c',
            'rpId' => 'dev-cash-track.app',
            'userVerification' => 'required',
            'extensions' => [
                'credProps' => true,
            ],
            'timeout' => 300000,
        ];
    }

    protected function makeRequestData(): string
    {
        return 'eyJpZCI6IkRmOWJxdnJRX1pYMFEzWGc1TklPSEEiLCJyYXdJZCI6IkRmOWJxdnJRX1pYMFEzWGc1TklPSEEiLCJyZXNwb25zZSI6eyJhdXRoZW50aWNhdG9yRGF0YSI6IkxCSXR2R0NWRlg0RzBuTXo1V3hfTGZ2ME9FdkcyaGVWMjhVVXFXOEpITllkQUFBQUFBIiwiY2xpZW50RGF0YUpTT04iOiJleUowZVhCbElqb2lkMlZpWVhWMGFHNHVaMlYwSWl3aVkyaGhiR3hsYm1kbElqb2lPREJtWXpFMU5XSXpaR0ZsTW1SalpHUmpZemd5WVROaE5tSXpPRFpsTlRVNU5EVm1aRFJtTUdVME5qQXlOVGRpTldJMlkyTXlNak5qTkRRNE1ESTVZeUlzSW05eWFXZHBiaUk2SW1oMGRIQnpPaTh2WkdWMkxXTmhjMmd0ZEhKaFkyc3VZWEJ3SWl3aVkzSnZjM05QY21sbmFXNGlPbVpoYkhObGZRIiwic2lnbmF0dXJlIjoiTUVZQ0lRRE04TENMWlp2N0RCQVY3dW1jUFY2UV9LTTU3b2tCWFRKaWx6WEtXUF9uY3dJaEFKQTVFQnZnVDZLZVFZVXppUEtkZjlfOGY5bmR3VGlEanJ5NzFibUtwUTZmIiwidXNlckhhbmRsZSI6Ik1RIn0sInR5cGUiOiJwdWJsaWMta2V5IiwiY2xpZW50RXh0ZW5zaW9uUmVzdWx0cyI6e30sImF1dGhlbnRpY2F0b3JBdHRhY2htZW50IjoicGxhdGZvcm0ifQ';
    }
}
