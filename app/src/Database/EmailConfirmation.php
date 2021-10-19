<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity(repository = "App\Repository\EmailConfirmationRepository")
 * @Cycle\Table(indexes={
 *     @Cycle\Table\Index(columns = {"token"}),
 *     @Cycle\Table\Index(columns = {"email"}, unique = true)
 * })
 */
class EmailConfirmation
{
    /**
     * @Cycle\Column(type = "string", primary = true)
     * @var string|null
     */
    public $email;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $token = '';

    /**
     * @Cycle\Column(type = "datetime", name = "created_at")
     * @var \DateTimeImmutable
     */
    public $createdAt;

    /**
     * EmailConfirmation constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
