<?php

declare(strict_types = 1);

namespace App\Service\EmailConfirmation;

use App\Config\AppConfig;
use App\Database\EmailConfirmation;
use App\Database\User;
use App\Mail\EmailConfirmationMail;
use App\Repository\EmailConfirmationRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Router\RouterInterface;

/**
 * @Prototyped(property="emailConfirmationService")
 */
class EmailConfirmationService
{
    const TTL = 60 * 60;
    const RESEND_TIME_LIMIT = 60;

    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    private $tr;

    /**
     * @var \App\Repository\EmailConfirmationRepository
     */
    private $confirmationRepository;

    /**
     * @var \App\Repository\UserRepository
     */
    private $userRepository;

    /**
     * @var \Spiral\Auth\AuthScope
     */
    private $mailer;

    /**
     * @var \Spiral\Router\RouterInterface
     */
    private $router;

    /**
     * @var \App\Config\AppConfig
     */
    private $appConfig;

    /**
     * AuthService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     * @param \App\Repository\EmailConfirmationRepository $confirmationRepository
     * @param \App\Repository\UserRepository $userRepository
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @param \Spiral\Router\RouterInterface $router
     * @param \App\Config\AppConfig $appConfig
     */
    public function __construct(
        TransactionInterface $tr,
        EmailConfirmationRepository $confirmationRepository,
        UserRepository $userRepository,
        MailerInterface $mailer,
        RouterInterface $router,
        AppConfig $appConfig
    ) {
        $this->tr                     = $tr;
        $this->confirmationRepository = $confirmationRepository;
        $this->userRepository         = $userRepository;
        $this->mailer                 = $mailer;
        $this->router                 = $router;
        $this->appConfig              = $appConfig;
    }

    /**
     * @param \App\Database\User $user
     * @throws \Throwable
     */
    public function create(User $user)
    {
        if ($user->isEmailConfirmed) {
            throw new \RuntimeException('You already confirmed your account email');
        }

        $confirmation            = new EmailConfirmation();
        $confirmation->email     = $user->email;
        $confirmation->token     = $this->generateToken();
        $confirmation->createdAt = new \DateTimeImmutable();

        $this->tr->persist($confirmation);
        $this->tr->run();

        $this->mailer->send(new EmailConfirmationMail($user, $this->getConfirmationLink($confirmation->token)));
    }

    /**
     * @param \App\Database\User $user
     * @throws \Throwable
     */
    public function reSend(User $user)
    {
        $confirmation = $this->confirmationRepository->findByPK($user->email);
        if ($confirmation instanceof EmailConfirmation) {
            if ($this->isConfirmationResendThrottled($confirmation)) {
                throw new \RuntimeException('Previous confirmation is already sent less than ' . self::RESEND_TIME_LIMIT . ' seconds ago');
            }

            $this->tr->delete($confirmation);
            $this->tr->run();
        }

        $this->create($user);
    }

    /**
     * @param string $token
     * @throws \Throwable
     */
    public function confirm(string $token)
    {
        $confirmation = $this->confirmationRepository->findByToken($token);

        if ( ! $confirmation instanceof EmailConfirmation) {
            throw new \RuntimeException('Wrong confirmation token');
        }

        if ($this->isConfirmationExpired($confirmation)) {
            throw new \RuntimeException('Confirmation link are expired');
        }

        $user = $this->userRepository->findByEmail($confirmation->email);

        if ( ! $user instanceof User) {
            throw new \RuntimeException('Unable to find user linked to confirmation link');
        }

        $user->isEmailConfirmed = true;

        $this->tr->persist($user);
        $this->tr->delete($confirmation);
        $this->tr->run();
    }

    /**
     * @param \App\Database\EmailConfirmation $confirmation
     * @return bool
     */
    private function isConfirmationResendThrottled(EmailConfirmation $confirmation): bool
    {
        return $confirmation->createdAt->getTimestamp() + self::RESEND_TIME_LIMIT > time();
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function generateToken(): string
    {
        return sha1((string) microtime(true) . bin2hex(random_bytes(256)));
    }

    /**
     * @param string $token
     * @return string
     */
    private function getConfirmationLink(string $token): string
    {
        $uri = $this->router->uri('auth.email.confirm', [
            'token' => $token,
        ]);

        return $this->appConfig->getUrl() . (string) $uri;
    }

    /**
     * @param \App\Database\EmailConfirmation $confirmation
     * @return bool
     */
    private function isConfirmationExpired(EmailConfirmation $confirmation): bool
    {
        return $confirmation->createdAt->getTimestamp() + self::TTL < time();
    }
}
