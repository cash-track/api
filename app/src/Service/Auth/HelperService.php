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

    /**
     * @var \Cycle\ORM\EntityManagerInterface
     */
    protected $tr;

    /**
     * @var \App\Repository\UserRepository
     */
    protected $userRepository;

    /**
     * @var \App\Service\Mailer\MailerInterface
     */
    protected $mailer;

    /**
     * @var \App\Service\UriService
     */
    protected $uri;

    /**
     * AuthService constructor.
     *
     * @param \Cycle\ORM\EntityManagerInterface $tr
     * @param \App\Repository\UserRepository $userRepository
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @param \App\Service\UriService $uri
     */
    public function __construct(
        EntityManagerInterface $tr,
        UserRepository $userRepository,
        MailerInterface $mailer,
        UriService $uri
    ) {
        $this->tr             = $tr;
        $this->userRepository = $userRepository;
        $this->mailer         = $mailer;
        $this->uri            = $uri;
    }

    /**
     * @param \DateTimeImmutable $date
     * @return bool
     */
    protected function isThrottled(\DateTimeImmutable $date): bool
    {
        return $date->getTimestamp() + self::RESEND_TIME_LIMIT > time();
    }

    /**
     * @param \DateTimeImmutable $date
     * @return bool
     */
    protected function isExpired(\DateTimeImmutable $date): bool
    {
        return $date->getTimestamp() + self::TTL < time();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function generateToken(): string
    {
        return sha1((string) microtime(true) . bin2hex(random_bytes(256)));
    }
}
