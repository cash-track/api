<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Database\ForgotPasswordRequest;
use App\Database\User;
use App\Mail\ForgotPasswordMail;
use App\Repository\ForgotPasswordRequestRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use App\Service\UriService;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="forgotPasswordService")
 */
class ForgotPasswordService extends HelperService
{
    /**
     * @var \App\Repository\ForgotPasswordRequestRepository
     */
    private $repository;

    /**
     * @var \App\Service\Auth\AuthService
     */
    private $authService;

    /**
     * ForgotPasswordService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     * @param \App\Repository\UserRepository $userRepository
     * @param \App\Service\Mailer\MailerInterface $mailer
     * @param \App\Service\UriService $uri
     * @param \App\Repository\ForgotPasswordRequestRepository $repository
     * @param \App\Service\Auth\AuthService $authService
     */
    public function __construct(
        TransactionInterface $tr,
        UserRepository $userRepository,
        MailerInterface $mailer,
        UriService $uri,
        ForgotPasswordRequestRepository $repository,
        AuthService $authService
    ) {
        parent::__construct($tr, $userRepository, $mailer, $uri);

        $this->repository     = $repository;
        $this->authService    = $authService;
    }

    /**
     * @param string $email
     * @return void
     * @throws \Throwable
     */
    public function create(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if (! $user instanceof User) {
            throw new \RuntimeException('Unable to find user by email');
        }

        $request = $this->repository->findByPK($email);

        if ($request instanceof ForgotPasswordRequest && $this->isThrottled($request->createdAt)) {
            throw new ForgotPasswordThrottledException('Previous request was created in less than ' . self::RESEND_TIME_LIMIT . ' seconds');
        }

        if ($request instanceof ForgotPasswordRequest) {
            $this->tr->delete($request);
            $this->tr->run();
        }

        $request            = new ForgotPasswordRequest();
        $request->email     = $user->email;
        $request->code      = $this->generateToken();
        $request->createdAt = new \DateTimeImmutable();

        $this->tr->persist($request);
        $this->tr->run();

        $this->mailer->send(new ForgotPasswordMail($user, $this->uri->passwordReset($request->code)));
    }

    /**
     * @param string $code
     * @param string $password
     * @return void
     * @throws \Throwable
     */
    public function reset(string $code, string $password): void
    {
        $request = $this->repository->findByCode($code);

        if (! $request instanceof ForgotPasswordRequest) {
            throw new \RuntimeException('Wrong password reset code');
        }

        if ($this->isExpired($request->createdAt)) {
            throw new \RuntimeException('Password reset link are expired');
        }

        $user = $this->userRepository->findByEmail($request->email);

        if (! $user instanceof User) {
            throw new \RuntimeException('Unable to find user linked to password reset link');
        }

        $this->authService->hashPassword($user, $password);

        $this->tr->persist($user);
        $this->tr->delete($request);
        $this->tr->run();
    }
}
