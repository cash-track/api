<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity(repository = "App\Repository\ForgotPasswordRequestRepository")
 * @Cycle\Table(indexes={
 *     @Cycle\Table\Index(columns = {"code"}),
 *     @Cycle\Table\Index(columns = {"email"}, unique = true)
 * })
 */
class ForgotPasswordRequest
{
    /**
     * @Cycle\Column(type = "string", primary = true)
     * @var string
     */
    public $email;

    /**
     * @Cycle\Column(type = "string")
     * @var string
     */
    public $code;

    /**
     * @Cycle\Column(type = "datetime", name = "created_at")
     * @var \DateTimeImmutable
     */
    public $createdAt;
}
