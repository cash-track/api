<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey;

use Illuminate\Contracts\Support\Arrayable;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * @template-implements Arrayable<string, string>
 */
class CreationChallenge implements Arrayable
{
    public function __construct(
        public string $name,
        public string $challenge,
        public PublicKeyCredentialCreationOptions|null $options = null,
    ) {
    }

    public static function fromArray(array $data): CreationChallenge
    {
        return new CreationChallenge(
            (string) ($data['name'] ?? ''),
            (string) ($data['challenge'] ?? ''),
            PublicKeyCredentialCreationOptions::createFromString($data['options'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'challenge' => $this->challenge,
            'options' => json_encode($this->options),
        ];
    }
}
