<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\Encrypter\EncrypterInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredential;

/**
 * @template-extends Repository<\App\Database\Passkey>
 */
class PasskeyRepository extends Repository
{
    /**
     * @param \Cycle\ORM\Select<\App\Database\Passkey> $select
     * @param \App\Service\Encrypter\EncrypterInterface $encrypter
     */
    public function __construct(
        Select $select,
        private readonly EncrypterInterface $encrypter,
    ) {
        parent::__construct($select);
    }

    public function findAllByUserPK(int $userID): array
    {
        /** @var \App\Database\Passkey[] $passkeys */
        $passkeys = $this->select()
                         ->where('user_id', $userID)
                         ->fetchAll();

        return $passkeys;
    }

    public function findByPKAndUserPK(int $id, int $userID): object|null
    {
        return $this->findOne([
            'id' => $id,
            'user_id' => $userID,
        ]);
    }

    public function findByKeyId(string $keyId): object|null
    {
        return $this->findOne([
            'key_id' => $this->encrypter->encrypt($keyId),
        ]);
    }

    public function findKeyByCredential(PublicKeyCredential $credential): object|null
    {
        return $this->findByKeyId(Base64UrlSafe::encodeUnpadded($credential->rawId));
    }
}
