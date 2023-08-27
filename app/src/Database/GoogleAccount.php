<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Typecast\EncryptedTypecast;
use App\Repository\ChargeRepository;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Entity\Behavior;
use Cycle\ORM\Parser\Typecast;

#[ORM\Entity(repository: ChargeRepository::class, typecast: [
    Typecast::class,
    EncryptedTypecast::class,
])]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class GoogleAccount
{
    #[ORM\Column(type: 'int', name: 'user_id', primary: true)]
    public int $userId = 0;

    #[ORM\Column(type: 'string', name: 'account_id', typecast: EncryptedTypecast::RULE)]
    public string $accountId = '';

    #[ORM\Column(type: 'string(1024)', name: 'picture_url', nullable: true, typecast: EncryptedTypecast::RULE)]
    public ?string $pictureUrl = null;

    #[ORM\Column(type: 'text', typecast: EncryptedTypecast::RULE)]
    public string $data = '';

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
        try {
            $this->data = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $_) {
            $this->data = '';
        }
    }
}
