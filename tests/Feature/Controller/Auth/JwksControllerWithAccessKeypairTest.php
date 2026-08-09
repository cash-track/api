<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use Firebase\JWT\JWT;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Tests\TestCase;
use Tests\Traits\ProvideRsaKeyPair;

/**
 * JwksControllerTest only covers the unconfigured (empty key set) path; this exercises the
 * populated response over real HTTP with a configured RSA keypair.
 *
 * The keypair is generated at runtime (openssl_pkey_new), which PHP attributes can't express
 * as a compile-time literal, so it's applied via beforeBooting()+Set rather than #[Env(...)].
 */
class JwksControllerWithAccessKeypairTest extends TestCase
{
    use ProvideRsaKeyPair;

    private string $publicKeyPem;

    protected function setUp(): void
    {
        $keyPair = $this->generateRsaKeyPair();
        $this->publicKeyPem = $keyPair['publicKey'];

        $this->beforeBooting(static function (ConfiguratorInterface $config) use ($keyPair): void {
            $config->modify('jwt', new Set('accessPublicKey', base64_encode($keyPair['publicKey'])));
            $config->modify('jwt', new Set('accessPrivateKey', base64_encode($keyPair['privateKey'])));
        });

        parent::setUp();
    }

    public function testReturnsPopulatedRfc7517KeySetOverHttp(): void
    {
        $response = $this->get('/.well-known/jwks.json');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('keys', $body);
        $this->assertCount(1, $body['keys']);

        $key = $body['keys'][0];

        $this->assertEquals('RSA', $key['kty']);
        $this->assertEquals('sig', $key['use']);
        $this->assertEquals('RS256', $key['alg']);
        $this->assertEquals(substr(hash('sha256', $this->publicKeyPem), 0, 16), $key['kid']);

        foreach (['n', 'e'] as $field) {
            $this->assertNotEmpty($key[$field]);
            $this->assertStringNotContainsString('+', $key[$field], "{$field} must be base64url, not base64");
            $this->assertStringNotContainsString('/', $key[$field], "{$field} must be base64url, not base64");
            $this->assertStringNotContainsString('=', $key[$field], "{$field} must be unpadded base64url");
        }

        // Compare against the actual modulus/exponent, not just the string shape, to catch a
        // regression that corrupts the value while keeping it non-empty and unpadded.
        $publicKeyResource = openssl_pkey_get_public($this->publicKeyPem);
        $this->assertNotFalse($publicKeyResource, 'Unable to load the test RSA public key.');

        $details = openssl_pkey_get_details($publicKeyResource);
        $this->assertIsArray($details);

        $this->assertSame($details['rsa']['n'], JWT::urlsafeB64Decode($key['n']));
        $this->assertSame($details['rsa']['e'], JWT::urlsafeB64Decode($key['e']));
    }
}
