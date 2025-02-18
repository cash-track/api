<?php

declare(strict_types=1);

namespace App\Database;

use App\Repository\CurrencyExchangeRepository;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Entity\Behavior;

/** @psalm-suppress InvalidArgument */
#[ORM\Entity(repository: CurrencyExchangeRepository::class)]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class CurrencyExchange
{
    #[ORM\Column(type: 'primary')]
    public int|null $id = null;

    #[ORM\Column(type: 'string(3)', name: 'src_currency_code')]
    public string $srcCurrencyCode = '';

    #[ORM\Column(type: 'decimal(13,2)', name: 'src_amount')]
    public float $srcAmount = 0.0;

    #[ORM\Column(type: 'decimal(8,4)')]
    public float $rate = 0.0;

    #[ORM\Column(type: 'string(3)', name: 'dst_currency_code')]
    public string $dstCurrencyCode = '';

    #[ORM\Column(type: 'decimal(13,2)', name: 'dst_amount')]
    public float $dstAmount = 0.0;

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\Relation\BelongsTo(target: Currency::class, innerKey: 'src_currency_code')]
    private Currency $srcCurrency;

    #[ORM\Relation\BelongsTo(target: Currency::class, innerKey: 'dst_currency_code')]
    private Currency $dstCurrency;

    public function __construct()
    {
        $this->srcCurrency = new Currency();
        $this->dstCurrency = new Currency();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getSrcCurrency(): Currency
    {
        return $this->srcCurrency;
    }

    public function setSrcCurrency(Currency $srcCurrency): void
    {
        $this->srcCurrency = $srcCurrency;
        $this->srcCurrencyCode = (string) $srcCurrency->code;
    }

    public function getDstCurrency(): Currency
    {
        return $this->dstCurrency;
    }

    public function setDstCurrency(Currency $dstCurrency): void
    {
        $this->dstCurrency = $dstCurrency;
        $this->dstCurrencyCode = (string) $dstCurrency->code;
    }
}
