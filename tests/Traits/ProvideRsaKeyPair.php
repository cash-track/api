<?php

declare(strict_types=1);

namespace Tests\Traits;

trait ProvideRsaKeyPair
{
    /**
     * @return array{privateKey: string, publicKey: string}
     */
    protected function generateRsaKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNotFalse($resource, 'Unable to generate a test RSA keypair.');

        openssl_pkey_export($resource, $privateKey);

        $details = openssl_pkey_get_details($resource);

        $this->assertIsArray($details);

        return [
            'privateKey' => $privateKey,
            'publicKey' => $details['key'],
        ];
    }
}
