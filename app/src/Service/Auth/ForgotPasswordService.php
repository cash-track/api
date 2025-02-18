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
use Spiral\Translator\Traits\TranslatorTrait;

final class ForgotPasswordService extends HelperService
{
    use TranslatorTrait;

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

    public function create(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if (! $user instanceof User) {
            throw new \RuntimeException($this->say('forgot_password_invalid_user'));
        }

        /** @var \App\Database\ForgotPasswordRequest|null $request */
        $request = $this->repository->findByPK($email);

        if ($request instanceof ForgotPasswordRequest && $this->isThrottled($request->createdAt)) {
            throw new ForgotPasswordThrottledException(
                sprintf($this->say('forgot_password_throttled'), self::RESEND_TIME_LIMIT)
            );
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

        $this->mailer->send(new ForgotPasswordMail($user->getEntityHeader(), $this->uri->passwordReset($request->code)));
    }

    public function store(ForgotPasswordRequest $request): ForgotPasswordRequest
    {
        $this->tr->persist($request);
        $this->tr->run();

        return $request;
    }

    public function reset(string $code, string $password): void
    {
        $request = $this->repository->findByCode($code);

        if (! $request instanceof ForgotPasswordRequest) {
            throw new \RuntimeException($this->say('forgot_password_invalid_code'));
        }

        if ($this->isExpired($request->createdAt)) {
            throw new \RuntimeException($this->say('forgot_password_expired'));
        }

        $user = $this->userRepository->findByEmail((string) $request->email);

        if (! $user instanceof User) {
            throw new \RuntimeException($this->say('forgot_password_missing_user'));
        }

        $this->authService->hashPassword($user, $password);

        $this->tr->persist($user);
        $this->tr->delete($request);
        $this->tr->run();
    }
}
