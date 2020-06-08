<?php

declare(strict_types = 1);

namespace App\Service;

use App\Config\AppConfig;
use App\Database\ForgotPasswordRequest;
use App\Database\User;
use App\Mail\ForgotPasswordMail;
use App\Repository\ForgotPasswordRequestRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Router\RouterInterface;

/**
 * @Prototyped(property="forgotPasswordService")
 */
class ForgotPasswordService
{
    const TTL = 60 * 60;
    const RESEND_TIME_LIMIT = 60;

    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    private $tr;

    /**
     * @var \App\Repository\ForgotPasswordRequestRepository
     */
    private $repository;

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
     * @var \App\Service\AuthService
     */
    private $authService;

    /**
     * ForgotPasswordService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     * @param \App\Repository\ForgotPasswordRequestRepository $forgotPasswordRequestRepository
     * @param \App\Repository\UserRepository $userRepository
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @param \Spiral\Router\RouterInterface $router
     * @param \App\Config\AppConfig $appConfig
     * @param \App\Service\AuthService $authService
     */
    public function __construct(
        TransactionInterface $tr,
        ForgotPasswordRequestRepository $forgotPasswordRequestRepository,
        UserRepository $userRepository,
        MailerInterface $mailer,
        RouterInterface $router,
        AppConfig $appConfig,
        AuthService $authService
    ) {
        $this->tr                     = $tr;
        $this->repository = $forgotPasswordRequestRepository;
        $this->userRepository         = $userRepository;
        $this->mailer                 = $mailer;
        $this->router                 = $router;
        $this->appConfig              = $appConfig;
        $this->authService = $authService;
    }

    /**
     * @param string $email
     * @throws \Throwable
     */
    public function create(string $email)
    {
        $user = $this->userRepository->findByEmail($email);
        if (! $user instanceof User) {
            throw new \RuntimeException('Unable to find user by email');
        }

        $request = $this->repository->findByPK($email);
        if ($request instanceof ForgotPasswordRequest && $this->isRequestThrottled($request)) {
            throw new \RuntimeException('Previous request was created in less than ' . self::RESEND_TIME_LIMIT . ' seconds');
        }

        $request = new ForgotPasswordRequest();
        $request->email = $user->email;
        $request->code = $this->generateToken();
        $request->createdAt = new \DateTimeImmutable();

        $this->tr->persist($request);
        $this->tr->run();

        $this->mailer->send(new ForgotPasswordMail($user, $this->getResetLink($request->code)));
    }

    /**
     * @param string $code
     * @param string $password
     * @throws \Throwable
     */
    public function reset(string $code, string $password)
    {
        $request = $this->repository->findByCode($code);

        if ( ! $request instanceof ForgotPasswordRequest) {
            throw new \RuntimeException('Wrong password reset code');
        }

        if ($this->isRequestExpired($request)) {
            throw new \RuntimeException('Password reset link are expired');
        }

        $user = $this->userRepository->findByEmail($request->email);

        if ( ! $user instanceof User) {
            throw new \RuntimeException('Unable to find user linked to password reset link');
        }

        $this->authService->hashPassword($user, $password);

        $this->tr->persist($user);
        $this->tr->delete($request);
        $this->tr->run();
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
     * @param string $code
     * @return string
     */
    private function getResetLink(string $code): string
    {
        // TODO. Implement this route on the fromtend side. Render password reset form.

        return $this->appConfig->getUrl() ."/auth/password/reset/{$code}";
    }

    /**
     * @param \App\Database\ForgotPasswordRequest $request
     * @return bool
     */
    private function isRequestThrottled(ForgotPasswordRequest $request): bool
    {
        return $request->createdAt->getTimestamp() + self::RESEND_TIME_LIMIT > time();
    }

    /**
     * @param \App\Database\ForgotPasswordRequest $request
     * @return bool
     */
    private function isRequestExpired(ForgotPasswordRequest $request): bool
    {
        return $request->createdAt->getTimestamp() + self::TTL < time();
    }
}
