<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\CurrencyRepository;
use Cycle\Annotated\Annotation as ORM;

/** @psalm-suppress InvalidArgument */
#[ORM\Entity(repository: CurrencyRepository::class)]
class Currency
{
    const string DEFAULT_CURRENCY_CODE = 'USD';

    #[ORM\Column(type: 'string(3)', primary: true)]
    public string|null $code = null;

    #[ORM\Column('string')]
    public string $name = '';

    #[ORM\Column('string(1)')]
    public string $char = '';

    #[ORM\Column('decimal(8,4)')]
    public float $rate = 0.0;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
