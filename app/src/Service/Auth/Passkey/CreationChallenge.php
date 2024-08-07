<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey;

use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * @template-implements Arrayable<string, string|null>
 */
class CreationChallenge implements Arrayable
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        public string $name,
        public string $challenge,
        public PublicKeyCredentialCreationOptions|null $options = null,
    ) {
    }

    public static function fromArray(array $data, SerializerInterface $serializer): CreationChallenge
    {
        $instance = $serializer->deserialize($data['options'] ?? '', PublicKeyCredentialCreationOptions::class, 'json');

        return new CreationChallenge(
            $serializer,
            (string) ($data['name'] ?? ''),
            (string) ($data['challenge'] ?? ''),
            $instance,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'challenge' => $this->challenge,
            'options' => $this->serializer->serialize($this->options ?? [], 'json'),
        ];
    }
}
