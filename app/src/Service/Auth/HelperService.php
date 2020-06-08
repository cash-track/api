<?php

declare(strict_types = 1);

namespace App\Service\Auth;

use App\Config\AppConfig;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use Cycle\ORM\TransactionInterface;
use Spiral\Router\RouterInterface;

abstract class HelperService
{
    const TTL = 60 * 60;
    const RESEND_TIME_LIMIT = 60;

    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    protected $tr;

    /**
     * @var \App\Repository\UserRepository
     */
    protected $userRepository;

    /**
     * @var \Spiral\Auth\AuthScope
     */
    protected $mailer;

    /**
     * @var \Spiral\Router\RouterInterface
     */
    protected $router;

    /**
     * @var \App\Config\AppConfig
     */
    protected $appConfig;

    /**
     * AuthService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     * @param \App\Repository\UserRepository $userRepository
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @param \Spiral\Router\RouterInterface $router
     * @param \App\Config\AppConfig $appConfig
     */
    public function __construct(
        TransactionInterface $tr,
        UserRepository $userRepository,
        MailerInterface $mailer,
        RouterInterface $router,
        AppConfig $appConfig
    ) {
        $this->tr                     = $tr;
        $this->userRepository         = $userRepository;
        $this->mailer                 = $mailer;
        $this->router                 = $router;
        $this->appConfig              = $appConfig;
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