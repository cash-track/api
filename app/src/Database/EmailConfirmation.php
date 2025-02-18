<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\EmailConfirmationRepository;
use App\Service\Auth\EmailConfirmationService;
use Cycle\Annotated\Annotation as ORM;

/** @psalm-suppress InvalidArgument */
#[ORM\Entity(repository: EmailConfirmationRepository::class)]
#[ORM\Table(indexes: [
    new ORM\Table\Index(columns: ['token']),
    new ORM\Table\Index(columns: ['email'], unique: true),
])]
class EmailConfirmation
{
    #[ORM\Column(type: 'string', primary: true)]
    public string|null $email = null;

    #[ORM\Column('string')]
    public string $token = '';

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getResendTimeLimit(): int
    {
        return EmailConfirmationService::RESEND_TIME_LIMIT;
    }

    public function getValidTimeLimit(): int
    {
        return EmailConfirmationService::TTL;
    }

    public function getTimeSentAgo(): int
    {
        return time() - $this->createdAt->getTimestamp();
    }

    public function getIsThrottled(): bool
    {
        return $this->createdAt->getTimestamp() + $this->getResendTimeLimit() > time();
    }

    public function getIsValid(): bool
    {
        return $this->createdAt->getTimestamp() + $this->getValidTimeLimit() > time();
    }
}
