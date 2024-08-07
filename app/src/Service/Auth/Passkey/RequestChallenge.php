<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey;

use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * @template-implements Arrayable<string, string|null>
 */
class RequestChallenge implements Arrayable
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        public string $challenge,
        public PublicKeyCredentialRequestOptions|null $options = null,
    ) {
    }

    public static function fromArray(array $data, SerializerInterface $serializer): RequestChallenge
    {
        $instance = $serializer->deserialize($data['options'] ?? '', PublicKeyCredentialRequestOptions::class, 'json');

        return new RequestChallenge($serializer, (string) ($data['challenge'] ?? ''), $instance);
    }

    public function toArray(): array
    {
        return [
            'challenge' => $this->challenge,
            'options' => $this->serializer->serialize($this->options ?? [], 'json'),
        ];
    }
}
