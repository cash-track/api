<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\EmailConfirmationRepository;
use Cycle\Annotated\Annotation as ORM;

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
}
