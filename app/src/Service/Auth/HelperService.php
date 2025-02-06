<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use App\Service\UriService;
use Cycle\ORM\EntityManagerInterface;

abstract class HelperService
{
    const int TTL = 60 * 60;
    const int RESEND_TIME_LIMIT = 60;

    public function __construct(
        protected readonly EntityManagerInterface $tr,
        protected readonly UserRepository $userRepository,
        protected readonly MailerInterface $mailer,
        protected readonly UriService $uri
    ) {
    }

    protected function isThrottled(\DateTimeImmutable $date): bool
    {
        return $date->getTimestamp() + self::RESEND_TIME_LIMIT > time();
    }

    protected function isExpired(\DateTimeImmutable $date): bool
    {
        return $date->getTimestamp() + self::TTL < time();
    }

    protected function generateToken(): string
    {
        return sha1((string) microtime(true) . bin2hex(random_bytes(256)));
    }
}
