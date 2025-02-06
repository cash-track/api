<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\Passkey;
use App\Database\User;
use Tests\Fixtures;

class PasskeyFactory extends AbstractFactory
{
    protected ?User $user = null;

    public function forUser(?User $user = null): PasskeyFactory
    {
        $this->user = $user;

        return $this;
    }

    public function create(?Passkey $passkey = null): Passkey
    {
        $passkey = $passkey ?? self::make();

        if ($this->user !== null) {
            $passkey->setUser($this->user);
        }

        $this->persist($passkey);

        return $passkey;
    }

    public static function make(): Passkey
    {
        $passkey = new Passkey();

        $passkey->name = Fixtures::string();
        $passkey->keyId = Fixtures::string();
        $passkey->createdAt = Fixtures::dateTime();
        $passkey->usedAt = Fixtures::dateTimeAfter($passkey->createdAt);
        $passkey->setData(['publicKeyCredentialId' => $passkey->keyId]);

        return $passkey;
    }
}
