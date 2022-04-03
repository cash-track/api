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
use Cycle\ORM\EntityManagerInterface;

class ForgotPasswordService extends HelperService
{
    public function __construct(
        EntityManagerInterface $tr,
        UserRepository $userRepository,
        MailerInterface $mailer,
        UriService $uri,
        private ForgotPasswordRequestRepository $repository,
        private AuthService $authService
    ) {
        parent::__construct($tr, $userRepository, $mailer, $uri);
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

        /** @var \App\Database\ForgotPasswordRequest|null $request */
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

        $this->store($request);

        $this->mailer->send(new ForgotPasswordMail($user, $this->uri->passwordReset($request->code)));
    }

    /**
     * @param \App\Database\ForgotPasswordRequest $request
     * @return \App\Database\ForgotPasswordRequest
     * @throws \Throwable
     */
    public function store(ForgotPasswordRequest $request): ForgotPasswordRequest
    {
        $this->tr->persist($request);
        $this->tr->run();

        return $request;
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

        $user = $this->userRepository->findByEmail((string) $request->email);

        if (! $user instanceof User) {
            throw new \RuntimeException('Unable to find user linked to password reset link');
        }

        $this->authService->hashPassword($user, $password);

        $this->tr->persist($user);
        $this->tr->delete($request);
        $this->tr->run();
    }
}
