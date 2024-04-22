<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey;

use Illuminate\Contracts\Support\Arrayable;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * @template-implements Arrayable<string, string>
 */
class RequestChallenge implements Arrayable
{
    public function __construct(
        public string $challenge,
        public PublicKeyCredentialRequestOptions|null $options = null
    ) {
    }

    public static function fromArray(array $data): RequestChallenge
    {
        return new RequestChallenge(
            (string) ($data['challenge'] ?? ''),
            PublicKeyCredentialRequestOptions::createFromString($data['options'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'challenge' => $this->challenge,
            'options' => json_encode($this->options),
        ];
    }
}
