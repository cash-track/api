<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\ForgotPasswordRequestRepository;
use Cycle\Annotated\Annotation as ORM;

#[ORM\Entity(repository: ForgotPasswordRequestRepository::class)]
#[ORM\Table(indexes: [
    new ORM\Table\Index(columns: ['code']),
    new ORM\Table\Index(columns: ['email'], unique: true),
])]
class ForgotPasswordRequest
{
    #[ORM\Column(type: 'string', primary: true)]
    public string|null $email = null;

    #[ORM\Column('string')]
    public string $code = '';

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
