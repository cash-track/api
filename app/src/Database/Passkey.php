<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Typecast\EncryptedTypecast;
use App\Repository\PasskeyRepository;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Entity\Behavior;
use Cycle\ORM\Parser\Typecast;

#[ORM\Entity(repository: PasskeyRepository::class, typecast: [
    Typecast::class,
    EncryptedTypecast::class,
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
class Passkey
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Column(type: 'int', name: 'user_id')]
    public int $userId = 0;

    #[ORM\Column(type: 'string(1536)', name: 'name', typecast: EncryptedTypecast::STORE)]
    public string $name = '';

    #[ORM\Column(type: 'string(6144)', name: 'key_id', typecast: EncryptedTypecast::QUERY)]
    public string $keyId = '';

    #[ORM\Column(type: 'text', typecast: EncryptedTypecast::STORE)]
    public string $data = '';

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'used_at', nullable: true, default: null)]
    public \DateTimeImmutable|null $usedAt = null;

    #[ORM\Relation\BelongsTo(target: User::class, innerKey: 'user_id', load: 'lazy')]
    private User $user;

    public function __construct()
    {
        $this->user = new User();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->userId = (int) $user->id;
    }

    public function getData(): array
    {
        if (empty($this->data)) {
            return [];
        }

        try {
            return (array) json_decode($this->data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $_) {
            return [];
        }
    }

    public function setData(array $data): void
    {
        array_key_exists('publicKeyCredentialId', $data) || throw new \RuntimeException('Invalid passkey data');
        $this->keyId = $data['publicKeyCredentialId'];

        try {
            $this->data = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $_) {
            $this->data = '';
        }
    }
}
