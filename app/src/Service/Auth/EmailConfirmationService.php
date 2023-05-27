<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Database\EmailConfirmation;
use App\Database\User;
use App\Mail\EmailConfirmationMail;
use App\Repository\EmailConfirmationRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use App\Service\UriService;
use Cycle\ORM\EntityManagerInterface;
use Spiral\Translator\Traits\TranslatorTrait;

class EmailConfirmationService extends HelperService
{
    use TranslatorTrait;

    public function __construct(
        EntityManagerInterface $tr,
        UserRepository $userRepository,
        MailerInterface $mailer,
        UriService $uri,
        protected EmailConfirmationRepository $repository
    ) {
        parent::__construct($tr, $userRepository, $mailer, $uri);
    }

    public function create(User $user): void
    {
        if ($user->isEmailConfirmed) {
            throw new \RuntimeException($this->say('email_confirmation_account_already_confirmed'));
        }

        $confirmation            = new EmailConfirmation();
        $confirmation->email     = $user->email;
        $confirmation->token     = $this->generateToken();
        $confirmation->createdAt = new \DateTimeImmutable();

        $this->store($confirmation);

        $this->mailer->send(new EmailConfirmationMail($user->getEntityHeader(), $this->uri->emailConfirmation($confirmation->token)));
    }

    public function store(EmailConfirmation $confirmation): EmailConfirmation
    {
        $this->tr->persist($confirmation);
        $this->tr->run();

        return $confirmation;
    }

    public function reSend(User $user): void
    {
        /** @var \App\Database\EmailConfirmation|null $confirmation */
        $confirmation = $this->repository->findByPK($user->email);
        if ($confirmation instanceof EmailConfirmation) {
            if ($this->isThrottled($confirmation->createdAt)) {
                throw new \RuntimeException(
                    sprintf($this->say('email_confirmation_throttled'), self::RESEND_TIME_LIMIT)
                );
            }

            $this->tr->delete($confirmation);
            $this->tr->run();
        }

        $this->create($user);
    }

    public function confirm(string $token): void
    {
        $confirmation = $this->repository->findByToken($token);

        if (! $confirmation instanceof EmailConfirmation) {
            throw new \RuntimeException($this->say('email_confirmation_invalid_token'));
        }

        if ($this->isExpired($confirmation->createdAt)) {
            throw new \RuntimeException($this->say('email_confirmation_expired'));
        }

        $user = $this->userRepository->findByEmail((string) $confirmation->email);

        if (! $user instanceof User) {
            throw new \RuntimeException($this->say('email_confirmation_invalid_user'));
        }

        $user->isEmailConfirmed = true;

        $this->tr->persist($user);
        $this->tr->delete($confirmation);
        $this->tr->run();
    }
}
