<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Typecast\EncryptedTypecast;
use App\Database\Typecast\JsonTypecast;
use App\Repository\UserRepository;
use App\Security\PasswordContainerInterface;
use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Entity\Behavior;
use Cycle\ORM\Parser\Typecast;

#[ORM\Entity(repository: UserRepository::class, typecast: [
    Typecast::class,
    JsonTypecast::class,
    EncryptedTypecast::class,
])]
#[ORM\Table(indexes: [
    new ORM\Table\Index(columns: ['nick_name'], unique: true),
    new ORM\Table\Index(columns: ['email'], unique: true),
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class User implements PasswordContainerInterface
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Column(type: 'string(1536)', typecast: EncryptedTypecast::STORE)]
    public string $name = '';

    #[ORM\Column(type: 'string(1536)', name: 'last_name', nullable: true, typecast: EncryptedTypecast::STORE)]
    public string|null $lastName = null;

    #[ORM\Column(type: 'string(767)', name: 'nick_name', typecast: EncryptedTypecast::QUERY)]
    public string $nickName = '';

    #[ORM\Column(type: 'string(676)', typecast: EncryptedTypecast::QUERY)]
    public string $email = '';

    #[ORM\Column(type: 'boolean', name: 'is_email_confirmed', default: false)]
    public bool $isEmailConfirmed = false;

    #[ORM\Column(type: 'string(255)', name: 'photo', nullable: true)]
    public string|null $photo = null;

    #[ORM\Column(type: 'string(3)', name: 'default_currency_code')]
    public string|null $defaultCurrencyCode = null;

    #[ORM\Column('string')]
    public string $password = '';

    #[ORM\Column(type: 'datetime', name: 'created_at')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', name: 'updated_at')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime', name: 'active_at', nullable: true, default: null)]
    public ?\DateTimeImmutable $activeAt = null;

    #[ORM\Relation\BelongsTo(target: Currency::class, innerKey: 'default_currency_code', cascade: true, load: 'eager')]
    private Currency $defaultCurrency;

    #[ORM\Column(type: 'json', name: 'options', typecast: JsonTypecast::RULE)]
    public array $options = [];

    #[ORM\Relation\HasMany(target: Tag::class, outerKey: 'user_id')]
    private array $tags = [];

    public function __construct()
    {
        $this->defaultCurrency = new Currency();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDefaultCurrency(): Currency
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency(Currency $currency): void
    {
        $this->defaultCurrency = $currency;
    }

    /**
     * {@inheritDoc}
     */
    public function getPasswordHash(): string
    {
        return $this->password;
    }

    /**
     * {@inheritDoc}
     */
    public function setPasswordHash(string $password): void
    {
        $this->password = $password;
    }

    public function fullName(): string
    {
        if ($this->lastName === null || $this->lastName === '') {
            return $this->name;
        }

        return "{$this->name} {$this->lastName}";
    }

    /**
     * @psalm-return \App\Database\EntityHeader<\App\Database\User>
     * @return \App\Database\EntityHeader<\App\Database\User>
     */
    public function getEntityHeader(): EntityHeader
    {
        /** @var \App\Database\EntityHeader<\App\Database\User> $header */
        $header = new EntityHeader(self::class, ['id' => $this->id]);

        return $header;
    }
}
